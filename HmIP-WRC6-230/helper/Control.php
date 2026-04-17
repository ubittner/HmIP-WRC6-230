<?php

declare(strict_types=1);

trait Control
{
    ######### Public Methods ##########

    public function Control_ToggleActive(bool $State, bool $ForceExecution = false): void
    {
        $this->SendDebug(__FUNCTION__, 'Status: ' . json_encode($State) . ', Ausführung erzwingen: ' . json_encode($ForceExecution), 0);
        //Deactivation
        if (!$State) {
            $this->SetValue('Active', false);
            $this->SendDebug(__FUNCTION__, 'Deaktivieren', 0);
            //Switch actuator, always perform the defined deactivation setting for the switch actuator
            $value = $this->ReadPropertyInteger('DeactivationSwitchActuator');
            if ($value === 0 || $value === 1) { //0 = off, 1 = on, 2 = no action
                $this->SwitchActuator_ToggleState((bool) $value, $ForceExecution, true);
            }
            //Status LEDs, always performs the defined deactivation brightness to all status LEDs
            foreach ($this->statusLEDs as $led) {
                $this->StatusLED_SetBrightness($led['channel'], $this->ReadPropertyInteger('DeactivationBrightnessSlider'), $ForceExecution, true);
            }
        }
        //Activation
        else {
            $this->SetValue('Active', true);
            $this->SendDebug(__FUNCTION__, 'Aktivieren', 0);
            //If we are in the deactivation period, we do not allow performing an activation
            if ($this->Control_CheckDeactivationPeriod()) {
                return;
            }
            //Switch actuator
            //We have no trigger, so we perform the defined activation setting for the switch actuator
            if (!$this->Control_ValidateTriggerList('SwitchActuatorTriggerList')) {
                $value = $this->ReadPropertyInteger('ActivationSwitchActuator');
                if ($value === 0 || $value === 1) { //0 = off, 1 = on, 2 = no action
                    $this->SwitchActuator_ToggleState((bool) $value, $ForceExecution);
                }
            } else {
                //We have a trigger, so we check the conditions
                $this->SwitchActuator_CheckTriggerConditions($ForceExecution);
            }
            //Status LEDs
            foreach ($this->statusLEDs as $led) {
                //We have no trigger, so we perform the defined activation brightness for this led
                if (!$this->Control_ValidateTriggerList($led['designation'] . 'TriggerList')) {
                    $this->StatusLED_SetBrightness($led['channel'], $this->ReadPropertyInteger('ActivationBrightnessSlider'), $ForceExecution);
                } else {
                    //We have a trigger, so we check the conditions
                    $this->StatusLED_CheckTriggerConditions($led['channel'], $ForceExecution);
                }
            }
        }
    }

    public function Control_ExecuteAutomaticUpdate(): void //Used by a timer
    {
        $this->Control_SetAutomaticUpdateTimer();
        if ($this->Control_CheckMaintenance()) {
            return;
        }
        $forceExecution = $this->ReadPropertyBoolean('ForceExecution');
        //Switch actuator
        if ($this->ReadPropertyBoolean('UpdateSwitchActuator')) {
            $this->SwitchActuator_UpdateState($forceExecution);
        }
        //Status LEDs
        if ($this->ReadPropertyBoolean('UpdateStatusLEDs')) {
            $this->StatusLED_UpdateState($forceExecution);
        }
    }

    public function Control_StartAutomaticDeactivation(): void //Used by a timer
    {
        $this->Control_ToggleActive(false);
        $this->Control_SetAutomaticDeactivationTimer();
    }

    public function Control_StopAutomaticDeactivation(): void //Used by a timer
    {
        $this->Control_SetAutomaticDeactivationTimer();
        $this->Control_ToggleActive(true);
    }

    public function Control_CreateCommandControlInstance(): void
    {
        $id = IPS_CreateInstance(self::ABLAUFSTEUERUNG_MODULE_GUID);
        if (is_int($id)) {
            IPS_SetName($id, 'Ablaufsteuerung');
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    ########## Protected Methods ##########

    protected function Control_CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }

    protected function Control_CheckOperationMode(): void
    {
        //We will have to operation modes, one is on system startup and the other is o normal system operation
        $systemStartup = $this->ReadAttributeBoolean('SystemStartup');
        //System startup
        if ($systemStartup) {
            $this->WriteAttributeBoolean('SystemStartup', false);
            $forceExecution = $this->ReadPropertyBoolean('ForceExecutionOnSystemStartup');
            //Automatic deactivation
            if ($this->ReadPropertyBoolean('UseAutomaticDeactivation')) {
                $state = true;
                //Check if we are in the deactivation period
                if ($this->Control_CheckDeactivationPeriod()) {
                    $state = false;
                }
                $this->Control_ToggleActive($state, $forceExecution);
            } else {
                //Manual operation mode
                $this->Control_ToggleActive($this->GetValue('Active'), $forceExecution);
            }
        }
        //Normal system operation
        else {
            $this->Control_ToggleActive($this->GetValue('Active'));
        }
    }

    protected function Control_LockSemaphore($Name): bool
    {
        $attempts = 1000;
        for ($i = 0; $i < $attempts; $i++) {
            if (IPS_SemaphoreEnter(__CLASS__ . '.' . $this->InstanceID . '.' . $Name, 1)) {
                $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' locked', 0);
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    protected function Control_UnlockSemaphore($Name): void
    {
        @IPS_SemaphoreLeave(__CLASS__ . '.' . $this->InstanceID . '.' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' unlocked', 0);
    }

    protected function Control_SetAutomaticUpdateTimer(): void
    {
        $milliseconds = 0;
        if ($this->ReadPropertyBoolean('AutomaticUpdate')) {
            $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        }
        $this->SetTimerInterval('AutomaticUpdate', $milliseconds);
    }

    protected function Control_SetAutomaticDeactivationTimer(): void
    {
        $use = $this->ReadPropertyBoolean('UseAutomaticDeactivation');
        //Deactivation start
        $milliseconds = 0;
        if ($use) {
            $milliseconds = $this->Control_GetTimerInterval('AutomaticDeactivationStartTime');
        }
        $this->SetTimerInterval('StartAutomaticDeactivation', $milliseconds);
        //Deactivation end
        $milliseconds = 0;
        if ($use) {
            $milliseconds = $this->Control_GetTimerInterval('AutomaticDeactivationEndTime');
        }
        $this->SetTimerInterval('StopAutomaticDeactivation', $milliseconds);
    }

    ########## Private Methods ##########

    private function Control_CheckDeactivationPeriod(): bool
    {
        if (!$this->ReadPropertyBoolean('UseAutomaticDeactivation')) {
            return false;
        }
        $start = $this->GetTimerInterval('StartAutomaticDeactivation');
        $stop = $this->GetTimerInterval('StopAutomaticDeactivation');
        $state = false;
        //Check deactivation period
        if ($start > $stop) {
            $state = true;
        }
        return $state;
    }

    private function Control_ValidateTriggerList(string $TriggerListName): bool
    {
        $result = false;
        $variables = json_decode($this->ReadPropertyString($TriggerListName), true);
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                if (!$variable['Use']) {
                    continue;
                }
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if (@IPS_VariableExists($id)) {
                                $result = true;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function Control_GetTimerInterval(string $TimerName): int
    {
        $timer = json_decode($this->ReadPropertyString($TimerName));
        $now = time();
        $hour = $timer->hour;
        $minute = $timer->minute;
        $second = $timer->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        return ($timestamp - $now) * 1000;
    }
}