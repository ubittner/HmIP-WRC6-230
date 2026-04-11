<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SwitchActuator
{
    ######### Public Methods  ##########

    /**
     * Switches the actuator to the specified state.
     *
     * @param bool $State
     * true =  switch on,
     * false = switch off
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always switch
     *
     * @return bool
     * true =  the actuator was switched successfully,
     * false = the actuator could not be switched
     *
     * @throws Exception
     */
    public function SwitchActuator(bool $State, bool $ForceExecution = false): bool
    {
        //Do some checks first
        if ($this->CheckMaintenance()) {
            return false;
        }
        $variableID = $this->ReadPropertyInteger('SwitchActuatorDeviceState');
        if (!@IPS_VariableExists($variableID)) {
            return false;
        }
        //Get and set the current state and set the new state
        $currentState = $this->GetValue('SwitchActuator');
        $this->SetValue('SwitchActuator', $State);
        //If the state is the same, also check the current device state
        if ($currentState == $State) {
            if (GetValueBoolean($this->ReadPropertyInteger('SwitchActuatorDeviceState')) != $State) {
                //Always force if the state is different
                $ForceExecution = true;
            }
        }
        if (!$ForceExecution) {
            if ($currentState == $State) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, es wird bereits der gleiche Status verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Das Schalten wird erzwungen!', 0);
        }
        //Enter semaphore
        if (!$this->LockSemaphore('SwitchActuator')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
            //Revert back to the origin state
            $this->SetValue('SwitchActuator', $currentState);
            //Exit semaphore
            $this->UnlockSemaphore('SwitchActuator');
            return false;
        }
        //Switch actuator
        $commandControl = $this->ReadPropertyInteger('CommandControl');
        $value = $State ? 'true' : 'false';
        if (@IPS_InstanceExists($commandControl)) {
            $commands = [];
            $commands[] = '@RequestAction(' . $variableID . ', ' . $value . ');';
            $commandsJson = json_encode($commands);
            $commandsJsonEncoded = json_encode($commandsJson);
            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . $commandsJsonEncoded . ');';
            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', json_encode(' . $commandsJson . '));', 0);
            $result = @IPS_RunScriptText($scriptText);
        } else {
            $switchingDelay = $this->ReadPropertyInteger('SwitchActuatorSwitchingDelay');
            IPS_Sleep($switchingDelay);
            $this->SendDebug(__FUNCTION__, 'Befehl: @RequestAction(' . $variableID . ', ' . $value . ');', 0);
            $result = @RequestAction($variableID, $State);
            if (!$result) {
                IPS_Sleep($switchingDelay);
                $result = @RequestAction($variableID, $State);
            }
        }
        if (!$result) {
            //Revert back to the origin state
            $this->SetValue('SwitchActuator', $currentState);
        }
        //Exit semaphore
        $this->UnlockSemaphore('SwitchActuator');
        return $result;
    }

    /**
     * Gets the current states of the triggers for the switch actuator.
     *
     * @return void
     */
    public function SwitchActuator_GetCurrentTriggerStates(): void
    {
        $this->UpdateFormField('SwitchActuatorCurrentVariableStateConfigurationButton', 'visible', false);
        $actualVariableStates = [];
        $variables = json_decode($this->ReadPropertyString('SwitchActuatorTriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $conditions = true;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($sensorID <= 1 || @!IPS_ObjectExists($sensorID)) {
                            $conditions = false;
                        }
                    }
                }
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || @!IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            if ($conditions && isset($sensorID)) {
                $stateName = 'Bedingung nicht erfüllt!';
                $rowColor = '#FFC0C0';
                if (IPS_IsConditionPassing($variable['PrimaryCondition']) && IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                    $stateName = 'Bedingung erfüllt';
                    $rowColor = '#C0FFC0';
                }
                $toggleAction = 'Aus';
                if ($variable['ToggleAction'] == 'true') {
                    $toggleAction = 'Ein';
                }
                $variableUpdate = IPS_GetVariable($sensorID)['VariableUpdated']; //timestamp or 0 for never
                $lastUpdate = 'Nie';
                if ($variableUpdate != 0) {
                    $lastUpdate = date('d.m.Y H:i:s', $variableUpdate);
                }
                $actualVariableStates[] = ['ActualStatus' => $stateName, 'SensorID' => $sensorID, 'Designation' =>  $variable['Designation'], 'ToggleAction' =>  $toggleAction, 'LastUpdate' => $lastUpdate, 'rowColor' => $rowColor];
            }
        }
        $amount = count($actualVariableStates);
        if ($amount == 0) {
            $amount = 1;
        }
        $field = 'SwitchActuatorCurrentVariableStateList';
        $this->UpdateFormField($field, 'rowCount', $amount);
        $this->UpdateFormField($field, 'values', json_encode($actualVariableStates));
        $this->UpdateFormField($field, 'visible', true);
    }

    /**
     * Updates the state of the switch actuator.
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always switch
     *
     * @return void
     *
     * @throws Exception
     */
    public function SwitchActuator_UpdateState(bool $ForceExecution): void
    {
        $this->LogMessage(' ID ' . $this->InstanceID . ', ' . __CLASS__ . ', ' . __FUNCTION__ . ', Forcieren: ' . json_encode($ForceExecution), KL_NOTIFY);
        $this->SwitchActuator_CheckTriggerConditions($ForceExecution);
    }

    ########## Protected Methods  ##########

    /**
     * Checks the trigger conditions for the switch actuator and performs the appropriate actions if conditions are met.
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always switch
     *
     * @return void
     *
     * @throws Exception
     */
    protected function SwitchActuator_CheckTriggerConditions(bool $ForceExecution = false): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $variables = json_decode($this->ReadPropertyString('SwitchActuatorTriggerList'), true);
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                $execute = false;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                if ($variable['Use']) {
                                    $condition = true;
                                    //Check primary condition
                                    if (!IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                                        $condition = false;
                                    }
                                    //Check secondary condition
                                    if (!IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                                        $condition = false;
                                    }
                                    if ($condition) {
                                        $execute = true;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($execute) {
                    if ($ForceExecution) {
                        $force = true;
                    } else {
                        $force = $variable['ForceExecution'];
                    }
                    $this->SwitchActuator($variable['ToggleAction'], $force);
                    break;

                }
            }
        }
    }

    /**
     * Checks whether a specific variable is assigned as a trigger for the switch actuator.
     *
     * @param int $VariableID
     * The ID of the variable to check.
     *
     * @return bool
     * true =  the variable is assigned as a trigger,
     * false = the variable is not assigned as a trigger.
     */
    protected function SwitchActuator_IsTriggerAssigned(int $VariableID): bool
    {
        $result = false;
        $variables = json_decode($this->ReadPropertyString('SwitchActuatorTriggerList'), true);
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                $conditions = ['PrimaryCondition', 'SecondaryCondition'];
                foreach ($conditions as $condition) {
                    if ($variable[$condition] != '') {
                        $conditionType = json_decode($variable[$condition], true);
                        if (array_key_exists(0, $conditionType)) {
                            if (array_key_exists(0, $conditionType[0]['rules']['variable'])) {
                                $id = $conditionType[0]['rules']['variable'][0]['variableID'];
                                if ($id == $VariableID) {
                                    if ($this->CheckVariableExits($id)) {
                                        if ($variable['Use']) {
                                            $result = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
}