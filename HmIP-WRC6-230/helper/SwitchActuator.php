<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait SwitchActuator
{
    ######### Public Methods ##########

    public function SwitchActuator_ToggleState(bool $State, bool $ForceExecution = false, bool $OverrideMaintenanceCheck = false): bool
    {
        //Do some checks first
        if (!$OverrideMaintenanceCheck) {
            if ($this->Control_CheckMaintenance()) {
                return false;
            }
        }
        $variableID = $this->ReadPropertyInteger('SwitchActuatorDeviceState');
        if (!@IPS_VariableExists($variableID)) {
            return false;
        }
        //Log
        //$this->LogMessage('ID ' . $this->InstanceID . ', ' . __CLASS__ . ', ' . __FUNCTION__ . ', Status: ' . json_encode($State) . ', Aktualisierung erzwingen: ' . json_encode($ForceExecution) . ', Wartungsprüfung überspringen: ' . json_encode($OverrideMaintenanceCheck), KL_NOTIFY);
        //Debug
        $this->SendDebug(__FUNCTION__, 'Status: ' . json_encode($State) . ', Aktualisierung erzwingen: ' . json_encode($ForceExecution) . ', Wartungsprüfung überspringen: ' . json_encode($OverrideMaintenanceCheck), 0);
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
        if (!$this->Control_LockSemaphore('SwitchActuator_ToggleState')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
            //Revert back to the origin state
            $this->SetValue('SwitchActuator', $currentState);
            //Exit semaphore
            $this->Control_UnlockSemaphore('SwitchActuator_ToggleState');
            return false;
        }
        //Switch actuator
        $result = false;
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
            try {
                $result = @RequestAction($variableID, $State);
            } catch (Exception $e) {
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . __CLASS__ . ' ' . __FUNCTION__ . ': ' . $e->getMessage(), KL_ERROR);
            }
            if (!$result) {
                IPS_Sleep($switchingDelay);
                try {
                    $result = @RequestAction($variableID, $State);
                } catch (Exception $e) {
                    $this->LogMessage('ID ' . $this->InstanceID . ', ' . __CLASS__ . ' ' . __FUNCTION__ . ': ' . $e->getMessage(), KL_ERROR);
                }
            }
        }
        if (!$result) {
            //Revert back to the origin state
            $this->SetValue('SwitchActuator', $currentState);
        }
        //Exit semaphore
        $this->Control_UnlockSemaphore('SwitchActuator_ToggleState');
        return $result;
    }

    public function SwitchActuator_UpdateState(bool $ForceExecution): void //Also used in configuration form
    {
        if ($this->Control_CheckMaintenance()) {
            return;
        }
        $this->SwitchActuator_CheckTriggerConditions($ForceExecution);
    }

    public function SwitchActuator_GetCurrentTriggerStates(): void //Only used from configuration form
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

    ######### Protected Methods ##########

    protected function SwitchActuator_CheckTriggerConditions(bool $ForceExecution = false): void
    {
        if ($this->Control_CheckMaintenance()) {
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
                    $this->SwitchActuator_ToggleState($variable['ToggleAction'], $force);
                    break;

                }
            }
        }
    }

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
                                    if (@IPS_VariableExists($id)) {
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