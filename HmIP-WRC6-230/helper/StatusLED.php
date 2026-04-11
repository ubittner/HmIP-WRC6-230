<?php

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait StatusLED
{
    ########## Declarations ##########

    protected array $statusLEDs = [
        [
            'channel'         => 12,
            'caption'         => 'Status LED oben links',
            'designation'     => 'StatusLED_UpperLeft',
            'colorIdent'      => 'StatusLED_UpperLeft_Color',
            'brightnessIdent' => 'StatusLED_UpperLeft_Brightness',
            'modeIdent'       => 'StatusLED_UpperLeft_Mode'
        ],
        [
            'channel'         => 13,
            'caption'         => 'Status LED oben rechts',
            'designation'     => 'StatusLED_UpperRight',
            'colorIdent'      => 'StatusLED_UpperRight_Color',
            'brightnessIdent' => 'StatusLED_UpperRight_Brightness',
            'modeIdent'       => 'StatusLED_UpperRight_Mode'
        ],
        [
            'channel'         => 14,
            'caption'         => 'Status LED mitte links',
            'designation'     => 'StatusLED_MidLeft',
            'colorIdent'      => 'StatusLED_MidLeft_Color',
            'brightnessIdent' => 'StatusLED_MidLeft_Brightness',
            'modeIdent'       => 'StatusLED_MidLeft_Mode'
        ],
        [
            'channel'         => 15,
            'caption'         => 'Status LED mitte rechts',
            'designation'     => 'StatusLED_MidRight',
            'colorIdent'      => 'StatusLED_MidRight_Color',
            'brightnessIdent' => 'StatusLED_MidRight_Brightness',
            'modeIdent'       => 'StatusLED_MidRight_Mode'
        ],
        [
            'channel'         => 16,
            'caption'         => 'Status LED unten links',
            'designation'     => 'StatusLED_LowerLeft',
            'colorIdent'      => 'StatusLED_LowerLeft_Color',
            'brightnessIdent' => 'StatusLED_LowerLeft_Brightness',
            'modeIdent'       => 'StatusLED_LowerLeft_Mode'
        ],
        [
            'channel'         => 17,
            'caption'         => 'Status LED unten rechts',
            'designation'     => 'StatusLED_LowerRight',
            'colorIdent'      => 'StatusLED_LowerRight_Color',
            'brightnessIdent' => 'StatusLED_LowerRight_Brightness',
            'modeIdent'       => 'StatusLED_LowerRight_Mode'
        ],
        [
            'channel'         => 18,
            'caption'         => 'Alle Status LEDs',
            'designation'     => 'StatusLED_All',
            'colorIdent'      => 'StatusLED_All_Color',
            'brightnessIdent' => 'StatusLED_All_Brightness',
            'modeIdent'       => 'StatusLED_All_Mode'
        ]
    ] {
        get {
            return $this->statusLEDs;
        }
    }

    protected array $colorTable = [
        0 => 'Aus',
        1 => 'Blau',
        2 => 'Grün',
        3 => 'Türkis',
        4 => 'Rot',
        5 => 'Violett',
        6 => 'Gelb',
        7 => 'Weiss'
    ] {
        get {
            return $this->colorTable;
        }
    }

    protected array $colorBehaviorTable = [
        0  => 'Beleuchtung aus',
        1  => 'Dauerhaft ein',
        2  => 'Langsames Blinken',
        3  => 'Mittleres Blinken',
        4  => 'Schnelles Blinken',
        5  => 'Langsames Blitzen',
        6  => 'Mittleres Blitzen',
        7  => 'Schnelles Blitzen',
        8  => 'Langsames Pulsieren',
        9  => 'Mittleres Pulsieren',
        10 => 'Schnelles Pulsieren',
        11 => 'Vorheriger Wert',
        12 => 'Ohne Berücksichtigung'
    ] {
        get {
            return $this->colorBehaviorTable;
        }
    }

    ########## Public Methods  ##########

    /**
     * Sets the color of a status LED.
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @param int $Color
     * 0 = black or off,
     * 1 = blue,
     * 2 = green,
     * 3 = turquoise,
     * 4 = red,
     * 5 = violet,
     * 6 = yellow,
     * 7 = white
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always adjust the color on the device
     *
     * @return bool
     * false =  an error occurred,
     * true =   execution successful
     *
     * @throws Exception
     */
    public function StatusLED_SetColor(int $Channel, int $Color, bool $ForceExecution = false): bool
    {
        //We do some checks first
        if (!$this->StatusLED_CheckExecution($Channel)) {
            return false;
        }
        if (!$this->StatusLED_IsColorValueValid($Color)) {
            return false;
        }
        $caption = $this->StatusLED_GetValueByChannel($Channel, 'caption');
        //Debug
        $this->SendDebug(__FUNCTION__, 'Gerätekanal: ' . $Channel . ', ' . $caption . ', Farbe: ' . $Color . ' = ' . $this->colorTable[$Color] . ', Forcieren: ' . json_encode($ForceExecution), 0);
        //Get the current color and set the new color
        $colorIdent = $this->StatusLED_GetValueByChannel($Channel, 'colorIdent');
        $currentColor = $this->GetValue($colorIdent);
        $this->SetValue($colorIdent, $Color);
        $designation = $this->StatusLED_GetValueByChannel($Channel, 'designation');
        //If it is the same color, check the color of the device
        if ($currentColor == $Color) {
            if (GetValueInteger($this->ReadPropertyInteger($designation . 'DeviceColor')) != $Color) {
                $ForceExecution = true;
            }
        }
        if (!$ForceExecution) {
            if ($currentColor == $Color) {
                $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Farbwert verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Der Farbwert wird erzwungen!', 0);
        }
        //Enter semaphore
        if (!$this->LockSemaphore('StatusLED_SetColor')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
            //Revert back to the origin color value
            $this->SetValue($colorIdent, $currentColor);
            //Exit semaphore
            $this->UnlockSemaphore('StatusLED_SetColor');
            return false;
        }
        //Set the color value on the device
        $deviceInstance = $this->ReadPropertyInteger($designation . 'DeviceInstance');
        $commandControl = $this->ReadPropertyInteger('CommandControl');
        if (@IPS_InstanceExists($commandControl)) {
            $commands = [];
            $commands[] = '@HM_WriteValueInteger(' . $deviceInstance . ", 'COLOR', '" . $Color . "');";
            $commandsJson = json_encode($commands);
            $commandsJsonEncoded = json_encode($commandsJson);
            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . $commandsJsonEncoded . ');';
            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', json_encode(' . $commandsJson . '));', 0);
            $result = @IPS_RunScriptText($scriptText);
        } else {
            IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $deviceInstance . ", 'COLOR', " . $Color . ');', 0);
            $result = @HM_WriteValueInteger($deviceInstance, 'COLOR', $Color);
            if (!$result) {
                IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
                $result = @HM_WriteValueInteger($deviceInstance, 'COLOR', $Color);
            }
        }
        if (!$result) {
            //Revert back to the origin color value
            $this->SetValue($colorIdent, $currentColor);
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Farbwert konnte für die ID ' . $deviceInstance . ' nicht eingestellt werden!', 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Der Farbwert wurde für die ID ' . $deviceInstance . ' eingestellt.', 0);
        }
        //Exit semaphore
        $this->UnlockSemaphore('StatusLED_SetColor');
        return $result;
    }

    /**
     * Sets the brightness of a status LED.
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @param int $Brightness
     * 0 to 100
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always adjust the brightness on the device
     *
     * @return bool
     * false =  an error occurred,
     * true =   execution successful
     *
     * @throws Exception
     */
    public function StatusLED_SetBrightness(int $Channel, int $Brightness, bool $ForceExecution = false): bool
    {
        //We do some checks first
        if (!$this->StatusLED_CheckExecution($Channel)) {
            return false;
        }
        if (!$this->StatusLED_IsBrightnessValueValid($Brightness)) {
            return false;
        }
        $caption = $this->StatusLED_GetValueByChannel($Channel, 'caption');
        //Debug
        $this->SendDebug(__FUNCTION__, 'Gerätekanal: ' . $Channel . ', ' . $caption . ', Helligkeit: ' . $Brightness . ', Forcieren: ' . json_encode($ForceExecution), 0);
        //Get the current brightness and set new brightness
        $brightnessIdent = $this->StatusLED_GetValueByChannel($Channel, 'brightnessIdent');
        $currentBrightness = $this->GetValue($brightnessIdent);
        $this->SetValue($brightnessIdent, $Brightness);
        $designation = $this->StatusLED_GetValueByChannel($Channel, 'designation');
        //If it is the same brightness, check the brightness of the device
        if ($currentBrightness == $Brightness) {
            if ((GetValueFloat($this->ReadPropertyInteger($designation . 'DeviceLevel')) * 100) != $Brightness) {
                $ForceExecution = true;
            }
        }
        if (!$ForceExecution) {
            if ($currentBrightness == $Brightness) {
                $this->SendDebug(__FUNCTION__, 'Es wird bereits die gleiche Helligkeit verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Die Helligkeit wird erzwungen!', 0);
        }
        //Enter semaphore
        if (!$this->LockSemaphore('StatusLED_SetBrightness')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
            //Revert back to the origin brightness value
            $this->SetValue($brightnessIdent, $currentBrightness);
            //Exit semaphore
            $this->UnlockSemaphore('StatusLED_SetBrightness');
            return false;
        }
        //Set the brightness on the device
        $deviceInstance = $this->ReadPropertyInteger($designation . 'DeviceInstance');
        $commandControl = $this->ReadPropertyInteger('CommandControl');
        if (@IPS_InstanceExists($commandControl)) {
            $commands = [];
            $commands[] = '@HM_WriteValueFloat(' . $deviceInstance . ", 'LEVEL', '" . $Brightness / 100 . "');";
            $commandsJson = json_encode($commands);
            $commandsJsonEncoded = json_encode($commandsJson);
            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . $commandsJsonEncoded . ');';
            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', json_encode(' . $commandsJson . '));', 0);
            $result = @IPS_RunScriptText($scriptText);
        } else {
            IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueFloat(' . $deviceInstance . ", 'LEVEL', " . $Brightness / 100 . ');', 0);
            $result = @HM_WriteValueFloat($deviceInstance, 'LEVEL', $Brightness / 100);
            if (!$result) {
                IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
                $result = @HM_WriteValueFloat($deviceInstance, 'LEVEL', $Brightness / 100);
            }
        }
        if (!$result) {
            //Revert back to the origin brightness value
            $this->SetValue($brightnessIdent, $currentBrightness);
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Helligkeitswert konnte für die ID ' . $deviceInstance . ' nicht eingestellt werden!', 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Der Helligkeitswert wurde für die ID ' . $deviceInstance . ' eingestellt.', 0);
        }
        //Exit semaphore
        $this->UnlockSemaphore('StatusLED_SetBrightness');
        return $result;
    }

    /**
     * Sets the color behavior of a status LED.
     *
     * @param int $Channel
     * 12 = LED upper left,
     * 13 = LED upper right,
     * 14 = LED mid left,
     * 15 = LED mid right,
     * 16 = LED lower left,
     * 17 = LED lower right
     * 18 = All LEDs
     *
     * @param int $ColorBehavior
     * 0 =  off,
     * 1 =  on,
     * 2 =  blinking slow,
     * 3 =  blinking middle,
     * 4 =  blinking fast,
     * 5 =  flash slow,
     * 6 =  flash middle,
     * 7 =  flash fast,
     * 8 =  billow slow,
     * 9 =  billow middle
     * 10 = billow falst
     * 11 = old value,
     * 12 = do not care
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always adjust the mode on the device
     *
     * @return bool
     * false =  an error occurred,
     * true =   execution successful
     *
     * @throws Exception
     */
    public function StatusLED_SetColorBehavior(int $Channel, int $ColorBehavior, bool $ForceExecution = false): bool
    {
        //We do some checks first
        if (!$this->StatusLED_CheckExecution($Channel)) {
            return false;
        }
        if (!$this->StatusLED_IsColorBehaviorValueValid($ColorBehavior)) {
            return false;
        }
        $caption = $this->StatusLED_GetValueByChannel($Channel, 'caption');
        //Debug
        $this->SendDebug(__FUNCTION__, 'Gerätekanal: ' . $Channel . ', ' . $caption . ', Farbmodus: ' . $ColorBehavior . ' = ' . $this->colorBehaviorTable[$ColorBehavior] . ', Forcieren: ' . json_encode($ForceExecution), 0);
        //Get the current mode and set the new mode
        $modeIdent = $this->StatusLED_GetValueByChannel($Channel, 'modeIdent');
        $currentMode = $this->GetValue($modeIdent);
        $this->SetValue($modeIdent, $ColorBehavior);
        $designation = $this->StatusLED_GetValueByChannel($Channel, 'designation');
        //If it is the same mode, check the color behavior of the device
        if ($currentMode == $ColorBehavior) {
            if (GetValueInteger($this->ReadPropertyInteger($designation . 'DeviceColorBehavior')) != $ColorBehavior) {
                $ForceExecution = true;
            }
        }
        if (!$ForceExecution) {
            if ($currentMode == $ColorBehavior) {
                $this->SendDebug(__FUNCTION__, 'Es wird bereits der gleiche Farbmodus verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Der Farbmodus wird erzwungen!', 0);
        }
        //Enter semaphore
        if (!$this->LockSemaphore('StatusLED_SetColorBehavior')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
            //Revert back to the origin mode value
            $this->SetValue($modeIdent, $currentMode);
            //Exit semaphore
            $this->UnlockSemaphore('StatusLED_SetColorBehavior');
            return false;
        }
        //Set the color behavior on the device
        $deviceInstance = $this->ReadPropertyInteger($designation . 'DeviceInstance');
        $commandControl = $this->ReadPropertyInteger('CommandControl');
        if (@IPS_InstanceExists($commandControl)) {
            $commands = [];
            $commands[] = '@HM_WriteValueInteger(' . $deviceInstance . ", 'COLOR_BEHAVIOUR', '" . $ColorBehavior . "');";
            $commandsJson = json_encode($commands);
            $commandsJsonEncoded = json_encode($commandsJson);
            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . $commandsJsonEncoded . ');';
            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', json_encode(' . $commandsJson . '));', 0);
            $result = @IPS_RunScriptText($scriptText);
        } else {
            IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $deviceInstance . ", 'COLOR_BEHAVIOUR', " . $ColorBehavior . ');', 0);
            $result = @HM_WriteValueInteger($deviceInstance, 'COLOR_BEHAVIOUR', $ColorBehavior);
            if (!$result) {
                IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
                $result = @HM_WriteValueInteger($deviceInstance, 'COLOR_BEHAVIOUR', $ColorBehavior);
            }
        }
        if (!$result) {
            //Revert back to the origin mode value
            $this->SetValue($modeIdent, $currentMode);
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Farbmodus konnte für die ID ' . $deviceInstance . ' nicht eingestellt werden!', 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Der Farbmodus wurde für die ID ' . $deviceInstance . ' eingestellt.', 0);
        }
        //Exit semaphore
        $this->UnlockSemaphore('StatusLED_SetColorBehavior');
        return $result;
    }

    /**
     * Sets the combined parameters of a status LED.
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @param int $Color
     * 0 = black or off,
     * 1 = blue,
     * 2 = green,
     * 3 = turquoise,
     * 4 = red,
     * 5 = violet,
     * 6 = yellow,
     * 7 = white
     *
     * @param int $Brightness
     * 0 to 100
     *
     * @param int $ColorBehavior
     * 0 =  off,
     * 1 =  on,
     * 2 =  blinking slow,
     * 3 =  blinking middle,
     * 4 =  blinking fast,
     * 5 =  flash slow,
     * 6 =  flash middle,
     * 7 =  flash fast,
     * 8 =  billow slow,
     * 9 =  billow middle,
     * 10 = billow falst,
     * 11 = old value,
     * 12 = do not care
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always adjust color, brightness and color behavior on the device
     *
     * @return bool
     * false =  an error occurred,
     * true =   execution successful
     *
     * @throws Exception
     */
    public function StatusLED_SetCombinedParameters(int $Channel, int $Color, int $Brightness, int $ColorBehavior, bool $ForceExecution = false): bool
    {
        //We do some checks first
        if (!$this->StatusLED_CheckExecution($Channel)) {
            return false;
        }
        if (!$this->StatusLED_IsColorValueValid($Color)) {
            return false;
        }
        if (!$this->StatusLED_IsBrightnessValueValid($Brightness)) {
            return false;
        }
        if (!$this->StatusLED_IsColorBehaviorValueValid($ColorBehavior)) {
            return false;
        }
        $caption = $this->StatusLED_GetValueByChannel($Channel, 'caption');
        //Debug
        $this->SendDebug(__FUNCTION__, $caption . ', Farbe: ' . $Color . ' = ' . $this->colorTable[$Color] . ', Helligkeit: ' . $Brightness . ', Farbmodus: ' . $ColorBehavior . ' = ' . $this->colorBehaviorTable[$ColorBehavior] . ', Forcieren: ' . json_encode($ForceExecution), 0);
        //Get current color and set the new color
        $colorIdent = $this->StatusLED_GetValueByChannel($Channel, 'colorIdent');
        $currentColor = $this->GetValue($colorIdent);
        $this->SetValue($colorIdent, $Color);
        $designation = $this->StatusLED_GetValueByChannel($Channel, 'designation');
        //If it is the same color, check the color of the device
        if ($currentColor == $Color) {
            if (GetValueInteger($this->ReadPropertyInteger($designation . 'DeviceColor')) != $Color) {
                $ForceExecution = true;
            }
        }
        //Get current brightness and set the new brightness
        $brightnessIdent = $this->StatusLED_GetValueByChannel($Channel, 'brightnessIdent');
        $currentBrightness = $this->GetValue($brightnessIdent);
        $this->SetValue($brightnessIdent, $Brightness);
        //If it is the same brightness, check the brightness of the device
        if ($currentBrightness == $Brightness) {
            if ((GetValueFloat($this->ReadPropertyInteger($designation . 'DeviceLevel')) * 100) != $Brightness) {
                $ForceExecution = true;
            }
        }
        //Get the current mode and set the new mode
        $modeIdent = $this->StatusLED_GetValueByChannel($Channel, 'modeIdent');
        $currentMode = $this->GetValue($modeIdent);
        $this->SetValue($modeIdent, $ColorBehavior);
        //If it is the same mode, check the color behavior of the device
        if ($currentMode == $ColorBehavior) {
            if (GetValueInteger($this->ReadPropertyInteger($designation . 'DeviceColorBehavior')) != $ColorBehavior) {
                $ForceExecution = true;
            }
        }
        if (!$ForceExecution) {
            if ($currentColor == $Color && $currentBrightness == $Brightness && $currentMode == $ColorBehavior) {
                $this->SendDebug(__FUNCTION__, 'Es werden bereits die gleichen Werte verwendet!', 0);
                return true;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'Die Werte werden erzwungen!', 0);
        }
        //Enter semaphore
        if (!$this->LockSemaphore('StatusLED_SetCombinedParameters')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, das Semaphore wurde erreicht!', 0);
            //Revert back to the origin values
            $this->SetValue($colorIdent, $currentColor);
            $this->SetValue($brightnessIdent, $currentBrightness);
            $this->SetValue($modeIdent, $currentMode);
            //Exit semaphore
            $this->UnlockSemaphore('StatusLED_SetCombinedParameters');
            return false;
        }
        //Set the values as combined parameters on the device
        $deviceInstance = $this->ReadPropertyInteger($designation . 'DeviceInstance');
        $commandControl = $this->ReadPropertyInteger('CommandControl');
        if ($this->CheckInstanceExits($commandControl)) {
            $commands = [];
            //C = color, L = level, CB = color behavior, DV = duration value, DU = duration unit, RTV = ramp time value, RTU = ramp time unit
            $commands[] = '@HM_WriteValueString(' . $deviceInstance . ", 'COMBINED_PARAMETER', 'C=" . $Color . ',L=' . $Brightness . ',CB=' . $ColorBehavior . "');";
            $commandsJson = json_encode($commands);
            $commandsJsonEncoded = json_encode($commandsJson);
            $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . $commandsJsonEncoded . ');';
            $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', json_encode(' . $commandsJson . '));', 0);
            $result = @IPS_RunScriptText($scriptText);
        } else {
            IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
            $this->SendDebug(__FUNCTION__, 'Befehl: @HM_WriteValueInteger(' . $deviceInstance . ", 'COMBINED_PARAMETER', C=" . $Color . ', L=' . $Brightness . ', CB=' . $ColorBehavior . ');', 0);
            $result = @HM_WriteValueString($deviceInstance, 'COMBINED_PARAMETER', 'C=' . $Color . ',L=' . $Brightness . ',CB=' . $ColorBehavior);
            // Try again
            if (!$result) {
                IPS_Sleep($this->ReadPropertyInteger($designation . 'SwitchingDelay'));
                $result = @HM_WriteValueString($deviceInstance, 'COMBINED_PARAMETER', 'C=' . $Color . ',L=' . $Brightness . ',CB=' . $ColorBehavior);
            }
        }
        if (!$result) {
            //Revert back to the origin values
            $this->SetValue($colorIdent, $currentColor);
            $this->SetValue($brightnessIdent, $currentBrightness);
            $this->SetValue($modeIdent, $currentMode);
            $this->SendDebug(__FUNCTION__, 'Abbruch, die kombinierten Werte konnten für die ID ' . $deviceInstance . ' nicht eingestellt werden!', 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Die kombinierten Werte wurden für die ID ' . $deviceInstance . ' eingestellt.', 0);
        }
        //Exit semaphore
        $this->UnlockSemaphore('StatusLED_SetCombinedParameters');
        return $result;
    }

    /**
     * Gets the current trigger states of the variables assigned to a status LED.
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @return void
     *
     * @throws Exception
     */
    public function StatusLED_GetCurrentTriggerStates(int $Channel): void
    {
        $designation = $this->StatusLED_GetValueByChannel($Channel, 'designation');
        $this->UpdateFormField($designation . 'ActualVariableStateConfigurationButton', 'visible', false);
        $actualVariableStates = [];
        $variables = json_decode($this->ReadPropertyString($designation . 'TriggerList'), true);
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
                        if (@!IPS_VariableExists($sensorID)) {
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
                                if (@!IPS_VariableExists($id)) {
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
                $colorName = $this->colorTable[$variable['Color']];
                $modeName = $this->colorBehaviorTable[$variable['Mode']];
                $variableUpdate = IPS_GetVariable($sensorID)['VariableUpdated']; //timestamp or 0 for never
                $lastUpdate = 'Nie';
                if ($variableUpdate != 0) {
                    $lastUpdate = date('d.m.Y H:i:s', $variableUpdate);
                }
                $actualVariableStates[] = ['ActualStatus' => $stateName, 'SensorID' => $sensorID, 'Priority' =>  $variable['Priority'], 'Designation' =>  $variable['Designation'], 'Color' =>  $colorName, 'Brightness' =>  $variable['Brightness'], 'Mode' =>  $modeName, 'LastUpdate' => $lastUpdate, 'rowColor' => $rowColor];
            }
        }
        $amount = count($actualVariableStates);
        if ($amount == 0) {
            $amount = 1;
        }
        $field = $designation . 'ActualVariableStateList';
        $this->UpdateFormField($field, 'rowCount', $amount);
        $this->UpdateFormField($field, 'values', json_encode($actualVariableStates));
        $this->UpdateFormField($field, 'visible', true);
    }

    /**
     * Updates the state of a status LED.
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   force execution
     *
     * @return void
     * @throws Exception
     */
    public function StatusLED_UpdateState(bool $ForceExecution): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Forcieren: ' . json_encode($ForceExecution), 0);
        $this->LogMessage(' ID ' . $this->InstanceID . ', ' . __CLASS__ . ', ' . __FUNCTION__ . ', Forcieren: ' . json_encode($ForceExecution), KL_NOTIFY);
        //Check conditions
        foreach ($this->statusLEDs as $led) {
            $this->StatusLED_CheckTriggerConditions($led['channel'], $ForceExecution);
        }
        //Set timer
        $milliseconds = 0;
        if ($this->ReadPropertyBoolean('AutomaticUpdate')) {
            $milliseconds = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        }
        $this->SetTimerInterval('AutomaticUpdate', $milliseconds);
    }

    ########## Protected Methods  ##########

    /**
     * Checks if the trigger variable is assigned to a status LED.
     *
     * @param int $VariableID
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @return bool
     * false =  trigger variable is not assigned,
     * true =   trigger variable is assigned
     * @throws Exception
     */
    protected function StatusLED_IsTriggerVariableAssigned(int $VariableID, int $Channel): bool
    {
        $result = false;
        $variables = json_decode($this->ReadPropertyString($this->StatusLED_GetValueByChannel($Channel, 'designation') . 'TriggerList'), true);
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

    /**
     * Checks the trigger conditions of a status LED and executes the match with the highest priority.
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @param bool $ForceExecution
     * false =  use configuration,
     * true =   always force execution
     *
     * @return void
     * @throws Exception
     */
    protected function StatusLED_CheckTriggerConditions(int $Channel, bool $ForceExecution): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $variables = json_decode($this->ReadPropertyString($this->StatusLED_GetValueByChannel($Channel, 'designation') . 'TriggerList'), true);
        if (!empty($variables)) {
            //Sort priority descending, highest priority first
            array_multisort(array_column($variables, 'Priority'), SORT_DESC, $variables);
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
                    $caption = $this->StatusLED_GetValueByChannel($Channel, 'caption');
                    $this->SendDebug(__FUNCTION__, $caption . ', Farbe: ' . $variable['Color'] . ' = ' . $this->colorTable[$variable['Color']], 0);
                    $this->SendDebug(__FUNCTION__, $caption . ', Helligkeit: ' . $variable['Brightness'], 0);
                    $this->SendDebug(__FUNCTION__, $caption . ', Modus: ' . $variable['Mode'] . ' = ' . $this->colorBehaviorTable[$variable['Mode']], 0);
                    $this->SendDebug(__FUNCTION__, $caption . ', Forcieren: ' . json_encode($force), 0);
                    $this->StatusLED_SetCombinedParameters($Channel, $variable['Color'], $variable['Brightness'], $variable['Mode'], $force);
                    break;

                }
            }
        }
    }

    /**
     * Gets the channel by the value of a status LED.
     * @param string $Value
     * @return int
     */
    protected function StatusLED_GetChannelByValue(string $Value): int
    {
        foreach ($this->statusLEDs as $led) {
            if ($led['colorIdent'] === $Value || $led['brightnessIdent'] === $Value || $led['modeIdent'] === $Value) {
                return $led['channel'];
            }
        }
        return 0;
    }

    ########## Private Methods ##########

    /**
     * Checks if the execution for a staus LED is possible.
     *
     * @param int $Channel
     * 12 = Status LED upper left,
     * 13 = Status LED upper right,
     * 14 = Status LED mid left,
     * 15 = Status LED mid right,
     * 16 = Status LED lower left,
     * 17 = Status LED lower right,
     * 18 = All Status LEDs
     *
     * @return bool
     * false =  execution is not possible,
     * true =   execution is possible
     * @throws Exception
     */
    private function StatusLED_CheckExecution(int $Channel): bool
    {
        if ($this->CheckMaintenance()) {
            return false;
        }
        //We only support the listed channels
        $channels = array_column($this->statusLEDs, 'channel');
        if (!in_array($Channel, $channels, true)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Gerätekanal ' . $Channel . ' wird nicht unterstützt!', 0);
            return false;
        }
        $designation = $this->StatusLED_GetValueByChannel($Channel, 'designation');
        $caption = $this->StatusLED_GetValueByChannel($Channel, 'caption');
        //Device instance
        if (!$this->CheckInstanceExits($this->ReadPropertyInteger($designation . 'DeviceInstance'))) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Geräteinstanz für Kanal ' . $Channel . ', ' . $caption . ' ist nicht konfiguriert!', 0);
            return false;
        }
        //Device color, device level, device color behavior
        $modes = ['DeviceColor', 'DeviceLevel', 'DeviceColorBehavior'];
        foreach ($modes as $mode) {
            if (!$this->CheckVariableExits($this->ReadPropertyInteger($designation . $mode))) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, die Gerätevariable ' . $mode . ' für ' . $caption . ' ist nicht konfiguriert!', 0);
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if the color value is valid.
     *
     * @param int $Color
     *
     * @return bool
     * false =  color value is not valid,
     * true =   color value is valid
     */
    private function StatusLED_IsColorValueValid(int $Color): bool
    {
        //We only support colors values from the color table
        if (!array_key_exists($Color, $this->colorTable)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wert ' . $Color . ' wird nicht unterstützt!', 0);
            return false;
        }
        return true;
    }

    /**
     * Checks if the brightness value is valid.
     *
     * @param int $Brightness
     *
     * @return bool
     * false =  brighness value is not valid,
     * true =   brightness value is valid
     */
    private function StatusLED_IsBrightnessValueValid(int $Brightness): bool
    {
        //We only support brightness values from 0 to 100
        if ($Brightness < 0 || $Brightness > 100) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wert ' . $Brightness . ' wird nicht unterstützt!', 0);
            return false;
        }
        return true;
    }

    /**
     * Checks if the color behavior value is valid.
     *
     * @param int $ColorBehavior
     *
     * @return bool
     * false =  color behavior value is not valid,
     * true =   color behavior value is valid
     */
    private function StatusLED_IsColorBehaviorValueValid(int $ColorBehavior): bool
    {
        //We only support the color behavior values from the color behavior table
        if (!array_key_exists($ColorBehavior, $this->colorBehaviorTable)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wert ' . $ColorBehavior . ' wird nicht unterstützt!', 0);
            return false;
        }
        return true;
    }

    /**
     * Retrieves the value of a specific property for a status LED identified by its channel.
     *
     * @param int $Channel
     * The channel of the status LED to query.
     *
     * @param string $Value
     * The property name of the status LED whose value is to be retrieved.
     *
     * @return string
     * Returns the value of the specified property for the given channel.
     * An empty string is returned if the channel or value is not found.
     *
     * @throws Exception
     */
    private function StatusLED_GetValueByChannel(int $Channel, string $Value): string
    {
        foreach ($this->statusLEDs as $led) {
            if ($led['channel'] === $Channel) {
                return $led[$Value];
            }
        }
        return '';
    }
}