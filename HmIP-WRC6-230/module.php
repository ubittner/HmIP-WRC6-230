<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class HMIPWRC6230 extends IPSModuleStrict
{
    //Helper
    use ConfigurationForm;
    use Control;
    use StatusLED;
    use SwitchActuator;

    //Constants
    private const string LIBRARY_GUID = '{14D20D32-F0E6-05DE-BACE-E523BBE25732}';
    private const string MODULE_GUID = '{98CA9F74-D5BB-69FF-BB44-7E9507DA5DED}';
    private const string MODULE_NAME = 'HmIP-WRC6-230';
    private const string MODULE_PREFIX = 'HMIPWRC6230';
    private const string ABLAUFSTEUERUNG_MODULE_GUID = '{0559B287-1052-A73E-B834-EBD9B62CB938}';
    private const string ABLAUFSTEUERUNG_MODULE_PREFIX = 'AST';

    ######### Public Methods  ##########

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Switch actuator
        $this->RegisterPropertyInteger('SwitchActuatorDeviceState', 0);
        $this->RegisterPropertyInteger('SwitchActuatorSwitchingDelay', 0);
        $this->RegisterPropertyString('SwitchActuatorTriggerList', '[]');

        //Status LEDs
        foreach ($this->statusLEDs as $statusLED) {
            $this->RegisterPropertyInteger($statusLED['designation'] . 'DeviceInstance', 0);
            $this->RegisterPropertyInteger($statusLED['designation'] . 'DeviceColor', 0);
            $this->RegisterPropertyInteger($statusLED['designation'] . 'DeviceLevel', 0);
            $this->RegisterPropertyInteger($statusLED['designation'] . 'DeviceColorBehavior', 0);
            $this->RegisterPropertyInteger($statusLED['designation'] . 'SwitchingDelay', 0);
            $this->RegisterPropertyString($statusLED['designation'] . 'TriggerList', '[]');
        }

        //Automatic status update
        $this->RegisterPropertyBoolean('AutomaticUpdate', false);
        $this->RegisterPropertyInteger('UpdateInterval', 1800);
        $this->RegisterPropertyBoolean('ForceExecution', true);

        //Command control
        $this->RegisterPropertyInteger('CommandControl', 0);

        //Deactivation
        $this->RegisterPropertyBoolean('UseAutomaticDeactivation', false);
        $this->RegisterPropertyString('AutomaticDeactivationStartTime', '{"hour":22,"minute":0,"second":0}');
        $this->RegisterPropertyString('AutomaticDeactivationEndTime', '{"hour":6,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('DeactivationBrightnessSlider', 0);

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableSwitchActuator', true);
        foreach ($this->statusLEDs as $statusLED) {
            $this->RegisterPropertyBoolean('Enable' . $statusLED['colorIdent'], true);
            $this->RegisterPropertyBoolean('Enable' . $statusLED['brightnessIdent'], true);
            $this->RegisterPropertyBoolean('Enable' . $statusLED['modeIdent'], true);
        }

        ##### Profiles

        //Color
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Color';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Bulb', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Blau', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, 'Grün', 'Bulb', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Türkis', 'Bulb', 0x01DFD7);
        IPS_SetVariableProfileAssociation($profile, 4, 'Rot', 'Bulb', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 5, 'Violett', 'Bulb', 0xB40486);
        IPS_SetVariableProfileAssociation($profile, 6, 'Gelb', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 7, 'Weiß', 'Bulb', 0xFFFFFF);

        //Mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Beleuchtung aus', '', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Dauerhaft ein', '', 0);
        IPS_SetVariableProfileAssociation($profile, 2, 'Langsames Blinken', '', 0);
        IPS_SetVariableProfileAssociation($profile, 3, 'Mittleres Blinken', '', 0);
        IPS_SetVariableProfileAssociation($profile, 4, 'Schnelles Blinken', '', 0);
        IPS_SetVariableProfileAssociation($profile, 5, 'Langsames Blitzen', '', 0);
        IPS_SetVariableProfileAssociation($profile, 6, 'Mittleres Blitzen', '', 0);
        IPS_SetVariableProfileAssociation($profile, 7, 'Schnelles Blitzen', '', 0);
        IPS_SetVariableProfileAssociation($profile, 8, 'Langsames Pulsieren', '', 0);
        IPS_SetVariableProfileAssociation($profile, 9, 'Mittleres Pulsieren', '', 0);
        IPS_SetVariableProfileAssociation($profile, 10, 'Schnelles Pulsieren', '', 0);
        IPS_SetVariableProfileAssociation($profile, 11, 'Vorheriger Wert', '', 0);
        IPS_SetVariableProfileAssociation($profile, 12, 'Ohne Berücksichtigung', '', 0);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Switch actuator
        $id = @$this->GetIDForIdent('SwitchActuator');
        $this->RegisterVariableBoolean('SwitchActuator', 'Schaltaktor', '~Switch', 20);
        $this->EnableAction('SwitchActuator');
        if (!$id) {
            $this->SetValue('SwitchActuator', false);
        }

        //Status LEDs
        $position = 20;
        foreach ($this->statusLEDs as $statusLED) {
            //Color
            $position += 10;
            $ident = $statusLED['colorIdent'];
            $id = @$this->GetIDForIdent($ident);
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Color';
            $this->RegisterVariableInteger($ident, $statusLED['caption'] . ' - Farbe', $profile, $position);
            $this->EnableAction($ident);
            if (!$id) {
                IPS_SetIcon($this->GetIDForIdent($ident), 'Bulb');
                $this->SetValue($ident, 0);
            }

            //Brightness
            $position += 10;
            $ident = $statusLED['brightnessIdent'];
            $id = @$this->GetIDForIdent($ident);
            $this->RegisterVariableInteger($ident, $statusLED['caption'] . ' - Helligkeit', '~Intensity.100', $position);
            $this->EnableAction($ident);
            if (!$id) {
                $this->SetValue($ident, 100);
            }

            //Mode
            $position += 10;
            $ident = $statusLED['modeIdent'];
            $id = @$this->GetIDForIdent($ident);
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Mode';
            $this->RegisterVariableInteger($ident, $statusLED['caption'] . ' - Modus', $profile, $position);
            $this->EnableAction($ident);
            if (!$id) {
                IPS_SetIcon($this->GetIDForIdent($ident), 'Gear');
                $this->SetValue($ident, 1);
            }
        }

        ########## Timers
        $this->RegisterTimer('StartAutomaticDeactivation', 0, self::MODULE_PREFIX . '_StartAutomaticDeactivation(' . $this->InstanceID . ');');
        $this->RegisterTimer('StopAutomaticDeactivation', 0, self::MODULE_PREFIX . '_StopAutomaticDeactivation(' . $this->InstanceID . ');');
        $this->RegisterTimer('AutomaticUpdate', 0, self::MODULE_PREFIX . '_AutomaticUpdate(' . $this->InstanceID . ');');
    }

    /**
     * @throws Exception
     */
    public function ApplyChanges(): void
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and update messages
        $references = [
            'SwitchActuatorDeviceState',
            'StatusLED_UpperLeftDeviceInstance',
            'StatusLED_UpperLeftDeviceColor',
            'StatusLED_UpperLeftDeviceLevel',
            'StatusLED_UpperLeftDeviceColorBehavior',
            'StatusLED_UpperRightDeviceInstance',
            'StatusLED_UpperRightDeviceColor',
            'StatusLED_UpperRightDeviceLevel',
            'StatusLED_UpperRightDeviceColorBehavior',
            'StatusLED_MidLeftDeviceInstance',
            'StatusLED_MidLeftDeviceColor',
            'StatusLED_MidLeftDeviceLevel',
            'StatusLED_MidLeftDeviceColorBehavior',
            'StatusLED_MidRightDeviceInstance',
            'StatusLED_MidRightDeviceColor',
            'StatusLED_MidRightDeviceLevel',
            'StatusLED_MidRightDeviceColorBehavior',
            'StatusLED_LowerLeftDeviceInstance',
            'StatusLED_LowerLeftDeviceColor',
            'StatusLED_LowerLeftDeviceLevel',
            'StatusLED_LowerLeftDeviceColorBehavior',
            'StatusLED_LowerRightDeviceInstance',
            'StatusLED_LowerRightDeviceColor',
            'StatusLED_LowerRightDeviceLevel',
            'StatusLED_LowerRightDeviceColorBehavior',
            'StatusLED_AllDeviceInstance',
            'StatusLED_AllDeviceColor',
            'StatusLED_AllDeviceLevel',
            'StatusLED_AllDeviceColorBehavior',
            'CommandControl'
        ];

        foreach ($references as $reference) {
            $id = $this->ReadPropertyInteger($reference);
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
            }
        }

        $triggerLists = [
            'SwitchActuatorTriggerList',
            'StatusLED_UpperLeftTriggerList',
            'StatusLED_UpperRightTriggerList',
            'StatusLED_MidLeftTriggerList',
            'StatusLED_MidRightTriggerList',
            'StatusLED_LowerLeftTriggerList',
            'StatusLED_LowerRightTriggerList',
            'StatusLED_AllTriggerList',
        ];
        foreach ($triggerLists as $list) {
            $variables = json_decode($this->ReadPropertyString($list), true);
            foreach ($variables as $variable) {
                if (!$variable['Use']) {
                    continue;
                }
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                $this->RegisterReference($id);
                                $this->RegisterMessage($id, VM_UPDATE);
                            }
                        }
                    }
                }
                //Secondary condition, multi
                if ($variable['SecondaryCondition'] != '') {
                    $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                    if (array_key_exists(0, $secondaryConditions)) {
                        if (array_key_exists('rules', $secondaryConditions[0])) {
                            $rules = $secondaryConditions[0]['rules']['variable'];
                            foreach ($rules as $rule) {
                                if (array_key_exists('variableID', $rule)) {
                                    $id = $rule['variableID'];
                                    if ($id > 1 && @IPS_ObjectExists($id)) {
                                        $this->RegisterReference($id);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('SwitchActuator'), !$this->ReadPropertyBoolean('EnableSwitchActuator'));
        foreach ($this->statusLEDs as $statusLED) {
            IPS_SetHidden($this->GetIDForIdent($statusLED['colorIdent']), !$this->ReadPropertyBoolean('Enable' . $statusLED['colorIdent']));
            IPS_SetHidden($this->GetIDForIdent($statusLED['brightnessIdent']), !$this->ReadPropertyBoolean('Enable' . $statusLED['brightnessIdent']));
            IPS_SetHidden($this->GetIDForIdent($statusLED['modeIdent']), !$this->ReadPropertyBoolean('Enable' . $statusLED['modeIdent']));
        }

        $switchActorDeviceState = $this->ReadPropertyInteger('SwitchActuatorDeviceState');
        if ($this->CheckVariableExits($switchActorDeviceState)) {
            $this->SetValue('SwitchActuator', GetValueBoolean($switchActorDeviceState));
        }

        $this->SetAutomaticDeactivationTimer();

        if ($this->CheckAutomaticDeactivationTimer()) {
            $this->ToggleActive(false);
        }

        //Set automatic update timer
        $milliseconds = 0;
        if ($this->ReadPropertyBoolean('AutomaticUpdate')) {
            $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        }
        $this->SetTimerInterval('AutomaticUpdate', $milliseconds);
    }

    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();

        //Delete created profiles for this instance
        $profiles = ['Color', 'Mode'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:

                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value

                if ($this->CheckMaintenance()) {
                    return;
                }

                if ($this->SwitchActuator_IsTriggerAssigned($SenderID)) {
                    $this->SwitchActuator_CheckTriggerConditions();
                }

                foreach ($this->statusLEDs as $led) {
                    if ($this->StatusLED_IsTriggerVariableAssigned($SenderID, $led['channel'])) {
                        $this->StatusLED_CheckTriggerConditions($led['channel'], false);
                    }
                }
                break;

        }
    }

    public function CreateCommandControlInstance(): void
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

    #################### Request Action

    /**
     * @throws Exception
     */
    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'Active':
                $this->ToggleActive($Value);
                break;

            case 'SwitchActuator':
                $this->SwitchActuator($Value);
                break;

                //Color
            case 'StatusLED_UpperLeft_Color':
            case 'StatusLED_UpperRight_Color':
            case 'StatusLED_MidLeft_Color':
            case 'StatusLED_MidRight_Color':
            case 'StatusLED_LowerLeft_Color':
            case 'StatusLED_LowerRight_Color':
            case 'StatusLED_All_Color':
                if (!$this->CheckMaintenance()) {
                    $this->StatusLED_SetColor($this->StatusLED_GetChannelByValue($Ident), $Value);
                }
                break;

                //Brightness
            case 'StatusLED_UpperLeft_Brightness':
            case 'StatusLED_UpperRight_Brightness':
            case 'StatusLED_MidLeft_Brightness':
            case 'StatusLED_MidRight_Brightness':
            case 'StatusLED_LowerLeft_Brightness':
            case 'StatusLED_LowerRight_Brightness':
            case 'StatusLED_All_Brightness':
                if (!$this->CheckMaintenance()) {
                    $this->StatusLED_SetBrightness($this->StatusLED_GetChannelByValue($Ident), $Value);
                }
                break;

                //Mode
            case 'StatusLED_UpperLeft_Mode':
            case 'StatusLED_UpperRight_Mode':
            case 'StatusLED_MidLeft_Mode':
            case 'StatusLED_MidRight_Mode':
            case 'StatusLED_LowerLeft_Mode':
            case 'StatusLED_LowerRight_Mode':
            case 'StatusLED_All_Mode':
                if (!$this->CheckMaintenance()) {
                    $this->StatusLED_SetColorBehavior($this->StatusLED_GetChannelByValue($Ident), $Value);
                }
                break;

        }
    }

    ########## Protected Methods  ##########

    protected function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }

    protected function CheckInstanceExits($InstanceID): bool
    {
        return @IPS_InstanceExists($InstanceID);
    }

    protected function CheckVariableExits($VariableID): bool
    {
        return @IPS_VariableExists($VariableID);
    }

    ########## Private Methods ##########

    /**
     * @throws Exception
     */
    private function KernelReady(): void
    {
        $this->ApplyChanges();
        $this->SwitchActuator_UpdateState(true);
        $this->StatusLED_UpdateState(true);
    }
}