<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait ConfigurationForm
{
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    public function ModifyActualVariableStatesConfigurationButton(string $Field, int $VariableID): void
    {
        $state = false;
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $VariableID . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $VariableID);
    }

    public function ModifyTriggerListButton(string $Field, string $Condition): void
    {
        $id = 0;
        $state = false;
        //Get variable id
        $primaryCondition = json_decode($Condition, true);
        if (array_key_exists(0, $primaryCondition)) {
            if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $state = true;
                }
            }
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $id . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $id);
    }

    public function GetConfigurationForm(): string
    {
        $panelCount = 1;
        $form = [];

        ########## Elements

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel' . $panelCount++,
            'caption'  => 'Info',
            'expanded' => false,
            'items'    => [
                [
                    'type'  => 'Image',
                    'image' => 'data:image/png;base64, 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'
                ],
                [
                    'type'    => 'Label',
                    'caption' => "\nID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\tHmIP-WRC6-230"
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        ##### Switch actuator

        $switchActuatorDeviceState = $this->ReadPropertyInteger('SwitchActuatorDeviceState');
        $enableSwitchActuatorDeviceStateConfigurationButton = false;
        if ($switchActuatorDeviceState > 1 && @IPS_ObjectExists($switchActuatorDeviceState)) {
            $enableSwitchActuatorDeviceStateConfigurationButton = true;
        }

        $switchActuatorTriggerListValues = [];
        $variables = json_decode($this->ReadPropertyString('SwitchActuatorTriggerList'), true);
        $switchActuatorTriggerListAmountRows = count($variables) + 1;
        if ($switchActuatorTriggerListAmountRows == 1) {
            $switchActuatorTriggerListAmountRows = 3;
        }
        $switchActuatorTriggerListAmountVariables = count($variables);
        foreach ($variables as $variable) {
            $sensorID = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                    }
                }
            }
            //Check conditions first
            $conditions = true;
            if ($sensorID <= 1 || !@IPS_ObjectExists($sensorID)) {
                $conditions = false;
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || !@IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            $rowColor = '#FFC0C0'; //red
            if ($conditions) {
                $rowColor = '#C0FFC0'; //light green
                if (!$variable['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $switchActuatorTriggerListValues[] = ['rowColor' => $rowColor];
        }

        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel' . $panelCount++,
                'caption' => 'Schaltaktor',
                'items'   => [
                    [
                        'type'    => 'Label',
                        'caption' => 'Gerätekanal 9',
                        'italic'  => true,
                        'bold'    => true
                    ],
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectVariable',
                                'name'     => 'SwitchActuatorDeviceState',
                                'caption'  => 'Gerätevariable STATE (Status)',
                                'width'    => '600px',
                                'onChange' => sprintf(
                                    '%s_ModifyButton($id, "SwitchActuatorDeviceStateConfigurationButton", "ID " . $SwitchActuatorDeviceState . " konfigurieren", $SwitchActuatorDeviceState);',
                                    self::MODULE_PREFIX,
                                )
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'SwitchActuatorDeviceStateConfigurationButton',
                                'caption'  => 'ID ' . $switchActuatorDeviceState . ' konfigurieren',
                                'visible'  => $enableSwitchActuatorDeviceStateConfigurationButton,
                                'objectID' => $switchActuatorDeviceState
                            ]
                        ]
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => ' '
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'SwitchActuatorSwitchingDelay',
                        'caption' => 'Schaltverzögerung',
                        'minimum' => 0,
                        'suffix'  => 'Millisekunden'
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => ' '
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Auslöser',
                        'italic'  => true,
                        'bold'    => true
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Aktueller Status',
                        'popup'   => [
                            'caption' => 'Aktueller Status',
                            'items'   => [
                                [
                                    'type'     => 'List',
                                    'name'     => 'SwitchActuatorCurrentVariableStateList',
                                    'add'      => false,
                                    'rowCount' => 1,
                                    'sort'     => [
                                        'column'    => 'ActualStatus',
                                        'direction' => 'ascending'
                                    ],
                                    'columns' => [
                                        [
                                            'name'    => 'ActualStatus',
                                            'caption' => 'Aktueller Status',
                                            'width'   => '250px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'SensorID',
                                            'caption' => 'ID',
                                            'width'   => '80px',
                                            'onClick' => sprintf(
                                                '%s_ModifyActualVariableStatesConfigurationButton($id, "SwitchActuatorCurrentVariableStateConfigurationButton", $SwitchActuatorCurrentVariableStateList["SensorID"]);',
                                                self::MODULE_PREFIX
                                            ),
                                            'save' => false
                                        ],
                                        [
                                            'name'    => 'Designation',
                                            'caption' => 'Bezeichnung',
                                            'width'   => '400px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'ToggleAction',
                                            'caption' => 'Schaltvorgang (Aus/Ein)',
                                            'width'   => '250px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'LastUpdate',
                                            'caption' => 'Letzte Aktualisierung',
                                            'width'   => '200px',
                                            'save'    => false
                                        ]
                                    ]
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => 'SwitchActuatorCurrentVariableStateConfigurationButton',
                                    'caption'  => 'Bearbeiten',
                                    'visible'  => false,
                                    'objectID' => 0
                                ]
                            ]
                        ],
                        'onClick' => sprintf(
                            '%s_SwitchActuator_GetCurrentTriggerStates($id);',
                            self::MODULE_PREFIX,
                        )
                    ],
                    [
                        'type'     => 'List',
                        'name'     => 'SwitchActuatorTriggerList',
                        'rowCount' => $switchActuatorTriggerListAmountRows,
                        'add'      => true,
                        'delete'   => true,
                        'sort'     => [
                            'column'    => 'Designation',
                            'direction' => 'ascending'
                        ],
                        'columns' => [
                            [
                                'caption' => 'Aktiviert',
                                'name'    => 'Use',
                                'width'   => '100px',
                                'add'     => true,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ],
                            [
                                'caption' => 'Bezeichnung',
                                'name'    => 'Designation',
                                'onClick' => sprintf(
                                    '%s_ModifyTriggerListButton($id, "SwitchActuatorTriggerListConfigurationButton", $SwitchActuatorTriggerList["PrimaryCondition"]);',
                                    self::MODULE_PREFIX,
                                ),
                                'width' => '300px',
                                'add'   => '',
                                'edit'  => [
                                    'type' => 'ValidationTextBox'
                                ]
                            ],
                            [
                                'caption' => 'Primäre Bedingung',
                                'name'    => 'PrimaryCondition',
                                'width'   => '1000px',
                                'add'     => '',
                                'visible' => false,
                                'edit'    => [
                                    'type' => 'SelectCondition'
                                ]
                            ],
                            [
                                'caption' => 'Weitere Bedingungen',
                                'name'    => 'SecondaryCondition',
                                'width'   => '1000px',
                                'add'     => '',
                                'visible' => false,
                                'edit'    => [
                                    'type'  => 'SelectCondition',
                                    'multi' => true
                                ]
                            ],
                            [
                                'caption' => 'Schaltvorgang (Aus/Ein)',
                                'name'    => 'ToggleAction',
                                'width'   => '250px',
                                'add'     => false,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ],
                            [
                                'caption' => 'Schaltvorgang erzwingen',
                                'name'    => 'ForceExecution',
                                'width'   => '250px',
                                'add'     => false,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ]
                        ],
                        'values' => $switchActuatorTriggerListValues
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Anzahl Auslöser: ' . $switchActuatorTriggerListAmountVariables,
                    ],
                    [
                        'type'     => 'OpenObjectButton',
                        'name'     => 'SwitchActuatorTriggerListConfigurationButton',
                        'caption'  => 'Bearbeiten',
                        'visible'  => false,
                        'objectID' => 0
                    ]
                ]
            ];

        ##### LEDs

        foreach ($this->statusLEDs as $statusLED) {

            //Device instance
            ${$statusLED['designation'] . 'DeviceInstance'} = $this->ReadPropertyInteger($statusLED['designation'] . 'DeviceInstance');
            ${'enable' . $statusLED['designation'] . 'DeviceInstanceConfigurationButton'} = false;
            if (${$statusLED['designation'] . 'DeviceInstance'} > 1 && @IPS_ObjectExists(${$statusLED['designation'] . 'DeviceInstance'})) {
                ${'enable' . $statusLED['designation'] . 'DeviceInstanceConfigurationButton'} = true;
            }

            //Device color
            ${$statusLED['designation'] . 'DeviceColor'} = $this->ReadPropertyInteger($statusLED['designation'] . 'DeviceColor');
            ${'enable' . $statusLED['designation'] . 'DeviceColorConfigurationButton'} = false;
            if (${$statusLED['designation'] . 'DeviceColor'} > 1 && @IPS_ObjectExists(${$statusLED['designation'] . 'DeviceColor'})) {
                ${'enable' . $statusLED['designation'] . 'DeviceColorConfigurationButton'} = true;
            }

            //Device color behavior
            ${$statusLED['designation'] . 'DeviceColorBehavior'} = $this->ReadPropertyInteger($statusLED['designation'] . 'DeviceColorBehavior');
            ${'enable' . $statusLED['designation'] . 'DeviceColorBehaviorConfigurationButton'} = false;
            if (${$statusLED['designation'] . 'DeviceColorBehavior'} > 1 && @IPS_ObjectExists(${$statusLED['designation'] . 'DeviceColorBehavior'})) {
                ${'enable' . $statusLED['designation'] . 'DeviceColorBehaviorConfigurationButton'} = true;
            }

            //Device brightness
            ${$statusLED['designation'] . 'DeviceLevel'} = $this->ReadPropertyInteger($statusLED['designation'] . 'DeviceLevel');
            ${'enable' . $statusLED['designation'] . 'DeviceLevelConfigurationButton'} = false;
            if (${$statusLED['designation'] . 'DeviceLevel'} > 1 && @IPS_ObjectExists(${$statusLED['designation'] . 'DeviceLevel'})) {
                ${'enable' . $statusLED['designation'] . 'DeviceLevelConfigurationButton'} = true;
            }

            //Trigger list
            ${$statusLED['designation'] . 'TriggerListValues'} = [];
            $variables = json_decode($this->ReadPropertyString($statusLED['designation'] . 'TriggerList'), true);
            ${$statusLED['designation'] . 'AmountRows'} = count($variables) + 1;
            if (${$statusLED['designation'] . 'AmountRows'} == 1) {
                ${$statusLED['designation'] . 'AmountRows'} = 3;
            }
            ${$statusLED['designation'] . 'AmountVariables'} = count($variables);
            foreach ($variables as $variable) {
                $sensorID = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                //Check conditions first
                $conditions = true;
                if ($sensorID <= 1 || !@IPS_ObjectExists($sensorID)) {
                    $conditions = false;
                }
                if ($variable['SecondaryCondition'] != '') {
                    $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                    if (array_key_exists(0, $secondaryConditions)) {
                        if (array_key_exists('rules', $secondaryConditions[0])) {
                            $rules = $secondaryConditions[0]['rules']['variable'];
                            foreach ($rules as $rule) {
                                if (array_key_exists('variableID', $rule)) {
                                    $id = $rule['variableID'];
                                    if ($id <= 1 || !@IPS_ObjectExists($id)) {
                                        $conditions = false;
                                    }
                                }
                            }
                        }
                    }
                }
                $rowColor = '#FFC0C0'; //red
                if ($conditions) {
                    $rowColor = '#C0FFC0'; //light green
                    if (!$variable['Use']) {
                        $rowColor = '#DFDFDF'; //grey
                    }
                }
                ${$statusLED['designation'] . 'TriggerListValues'}[] = ['rowColor' => $rowColor];
            }

        }

        foreach ($this->statusLEDs as $statusLED) {
            $form['elements'][] =
                [
                    'type'    => 'ExpansionPanel',
                    'name'    => 'Panel' . $panelCount++,
                    'caption' => $statusLED['caption'],
                    'items'   => [
                        [
                            'type'    => 'Label',
                            'caption' => 'Gerätekanal ' . $statusLED['channel'],
                            'italic'  => true,
                            'bold'    => true
                        ],
                        [
                            'type'  => 'RowLayout',
                            'items' => [
                                [
                                    'type'     => 'SelectInstance',
                                    'name'     => $statusLED['designation'] . 'DeviceInstance',
                                    'caption'  => 'Geräteinstanz',
                                    'width'    => '600px',
                                    'onChange' => sprintf(
                                        '%s_ModifyButton($id, "%sDeviceInstanceConfigurationButton", "ID " . $%s . " konfigurieren", $%s);',
                                        self::MODULE_PREFIX,
                                        $statusLED['designation'],
                                        $statusLED['designation'] . 'DeviceInstance',
                                        $statusLED['designation'] . 'DeviceInstance'
                                    )
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => $statusLED['designation'] . 'DeviceInstanceConfigurationButton',
                                    'caption'  => 'ID ' . ${$statusLED['designation'] . 'DeviceInstance'} . ' konfigurieren',
                                    'visible'  => ${'enable' . $statusLED['designation'] . 'DeviceInstanceConfigurationButton'},
                                    'objectID' => ${$statusLED['designation'] . 'DeviceInstance'}
                                ]
                            ]
                        ],
                        [
                            'type'  => 'RowLayout',
                            'items' => [
                                [
                                    'type'     => 'SelectVariable',
                                    'name'     => $statusLED['designation'] . 'DeviceColor',
                                    'caption'  => 'Gerätevariable COLOR (Farbe)',
                                    'width'    => '600px',
                                    'onChange' => sprintf(
                                        '%s_ModifyButton($id, "%sDeviceColorConfigurationButton", "ID " . $%s . " konfigurieren", $%s);',
                                        self::MODULE_PREFIX,
                                        $statusLED['designation'],
                                        $statusLED['designation'] . 'DeviceColor',
                                        $statusLED['designation'] . 'DeviceColor'
                                    )
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => $statusLED['designation'] . 'DeviceColorConfigurationButton',
                                    'caption'  => 'ID ' . ${$statusLED['designation'] . 'DeviceColor'} . ' konfigurieren',
                                    'visible'  => ${'enable' . $statusLED['designation'] . 'DeviceColorConfigurationButton'},
                                    'objectID' => ${$statusLED['designation'] . 'DeviceColor'}
                                ]
                            ]
                        ],
                        [
                            'type'  => 'RowLayout',
                            'items' => [
                                [
                                    'type'     => 'SelectVariable',
                                    'name'     => $statusLED['designation'] . 'DeviceColorBehavior',
                                    'caption'  => 'Gerätevariable COLOR_BEHAVIOUR (Modus)',
                                    'width'    => '600px',
                                    'onChange' => sprintf(
                                        '%s_ModifyButton($id, "%sDeviceColorBehaviorConfigurationButton", "ID " . $%s . " konfigurieren", $%s);',
                                        self::MODULE_PREFIX,
                                        $statusLED['designation'],
                                        $statusLED['designation'] . 'DeviceColorBehavior',
                                        $statusLED['designation'] . 'DeviceColorBehavior'
                                    )
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => $statusLED['designation'] . 'DeviceColorBehaviorConfigurationButton',
                                    'caption'  => 'ID ' . ${$statusLED['designation'] . 'DeviceColorBehavior'} . ' konfigurieren',
                                    'visible'  => ${'enable' . $statusLED['designation'] . 'DeviceColorBehaviorConfigurationButton'},
                                    'objectID' => ${$statusLED['designation'] . 'DeviceColorBehavior'}
                                ]
                            ]
                        ],
                        [
                            'type'  => 'RowLayout',
                            'items' => [
                                [
                                    'type'     => 'SelectVariable',
                                    'name'     => $statusLED['designation'] . 'DeviceLevel',
                                    'caption'  => 'Gerätevariable LEVEL (Helligkeit)',
                                    'width'    => '600px',
                                    'onChange' => sprintf(
                                        '%s_ModifyButton($id, "%sDeviceLevelConfigurationButton", "ID " . $%s . " konfigurieren", $%s);',
                                        self::MODULE_PREFIX,
                                        $statusLED['designation'],
                                        $statusLED['designation'] . 'DeviceLevel',
                                        $statusLED['designation'] . 'DeviceLevel'
                                    )
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => $statusLED['designation'] . 'DeviceLevelConfigurationButton',
                                    'caption'  => 'ID ' . ${$statusLED['designation'] . 'DeviceLevel'} . ' konfigurieren',
                                    'visible'  => ${'enable' . $statusLED['designation'] . 'DeviceLevelConfigurationButton'},
                                    'objectID' => ${$statusLED['designation'] . 'DeviceLevel'}
                                ]
                            ]
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'NumberSpinner',
                            'name'    => $statusLED['designation'] . 'SwitchingDelay',
                            'caption' => 'Schaltverzögerung',
                            'minimum' => 0,
                            'suffix'  => 'Millisekunden'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'Auslöser',
                            'italic'  => true,
                            'bold'    => true
                        ],
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Aktueller Status',
                            'popup'   => [
                                'caption' => 'Aktueller Status',
                                'items'   => [
                                    [
                                        'type'     => 'List',
                                        'name'     => $statusLED['designation'] . 'ActualVariableStateList',
                                        'add'      => false,
                                        'rowCount' => 1,
                                        'sort'     => [
                                            'column'    => 'ActualStatus',
                                            'direction' => 'ascending'
                                        ],
                                        'columns' => [
                                            [
                                                'name'    => 'ActualStatus',
                                                'caption' => 'Aktueller Status',
                                                'width'   => '250px',
                                                'save'    => false
                                            ],
                                            [
                                                'name'    => 'SensorID',
                                                'caption' => 'ID',
                                                'width'   => '80px',
                                                'onClick' => sprintf(
                                                    '%s_ModifyActualVariableStatesConfigurationButton($id, "%sActualVariableStateConfigurationButton", $%sActualVariableStateList["SensorID"]);',
                                                    self::MODULE_PREFIX,
                                                    $statusLED['designation'],
                                                    $statusLED['designation'],
                                                ),
                                                'save' => false
                                            ],
                                            [
                                                'name'    => 'Priority',
                                                'caption' => 'Priorität',
                                                'width'   => '100px',
                                                'save'    => false
                                            ],
                                            [
                                                'name'    => 'Designation',
                                                'caption' => 'Bezeichnung',
                                                'width'   => '400px',
                                                'save'    => false
                                            ],
                                            [
                                                'name'    => 'Color',
                                                'caption' => 'Farbe',
                                                'width'   => '120px',
                                                'save'    => false
                                            ],
                                            [
                                                'name'    => 'Mode',
                                                'caption' => 'Modus',
                                                'width'   => '200px',
                                                'save'    => false
                                            ],
                                            [
                                                'name'    => 'Brightness',
                                                'caption' => 'Helligkeit',
                                                'width'   => '120px',
                                                'save'    => false
                                            ],
                                            [
                                                'name'    => 'LastUpdate',
                                                'caption' => 'Letzte Aktualisierung',
                                                'width'   => '200px',
                                                'save'    => false
                                            ]
                                        ]
                                    ],
                                    [
                                        'type'     => 'OpenObjectButton',
                                        'name'     => $statusLED['designation'] . 'ActualVariableStateConfigurationButton',
                                        'caption'  => 'Bearbeiten',
                                        'visible'  => false,
                                        'objectID' => 0
                                    ]
                                ]
                            ],
                            'onClick' => sprintf(
                                '%s_StatusLED_GetCurrentTriggerStates($id, "%s");',
                                self::MODULE_PREFIX,
                                $statusLED['channel']
                            )
                        ],
                        [
                            'type'     => 'List',
                            'name'     => $statusLED['designation'] . 'TriggerList',
                            'rowCount' => ${$statusLED['designation'] . 'AmountRows'},
                            'add'      => true,
                            'delete'   => true,
                            'sort'     => [
                                'column'    => 'Priority',
                                'direction' => 'descending'
                            ],
                            'columns' => [
                                [
                                    'caption' => 'Aktiviert',
                                    'name'    => 'Use',
                                    'width'   => '100px',
                                    'add'     => true,
                                    'edit'    => [
                                        'type' => 'CheckBox'
                                    ]
                                ],
                                [
                                    'caption' => 'Priorität',
                                    'name'    => 'Priority',
                                    'width'   => '150px',
                                    'add'     => 1,
                                    'edit'    => [
                                        'type'    => 'Select',
                                        'options' => [
                                            [
                                                'caption' => '1 - niedrig',
                                                'value'   => 1
                                            ],
                                            [
                                                'caption' => '2',
                                                'value'   => 2
                                            ],
                                            [
                                                'caption' => '3',
                                                'value'   => 3
                                            ],
                                            [
                                                'caption' => '4 - mittel',
                                                'value'   => 4
                                            ],
                                            [
                                                'caption' => '5 - mittel',
                                                'value'   => 5
                                            ],
                                            [
                                                'caption' => '6',
                                                'value'   => 6
                                            ],
                                            [
                                                'caption' => '7',
                                                'value'   => 7
                                            ],
                                            [
                                                'caption' => '8 - hoch',
                                                'value'   => 8
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'caption' => 'Bezeichnung',
                                    'name'    => 'Designation',
                                    'onClick' => sprintf(
                                        '%s_ModifyTriggerListButton($id, "%sTriggerListConfigurationButton", $%sTriggerList["PrimaryCondition"]);',
                                        self::MODULE_PREFIX,
                                        $statusLED['designation'],
                                        $statusLED['designation']
                                    ),
                                    'width' => '300px',

                                    'add'  => '',
                                    'edit' => [
                                        'type' => 'ValidationTextBox'
                                    ]
                                ],
                                [
                                    'caption' => 'Primäre Bedingung',
                                    'name'    => 'PrimaryCondition',
                                    'width'   => '1000px',
                                    'add'     => '',
                                    'visible' => false,
                                    'edit'    => [
                                        'type' => 'SelectCondition'
                                    ]
                                ],
                                [
                                    'caption' => 'Weitere Bedingungen',
                                    'name'    => 'SecondaryCondition',
                                    'width'   => '1000px',
                                    'add'     => '',
                                    'visible' => false,
                                    'edit'    => [
                                        'type'  => 'SelectCondition',
                                        'multi' => true
                                    ]
                                ],
                                [
                                    'caption' => 'Farbe',
                                    'name'    => 'Color',
                                    'width'   => '100px',
                                    'add'     => 0,
                                    'edit'    => [
                                        'type'    => 'Select',
                                        'options' => [
                                            [
                                                'caption' => 'Aus',
                                                'value'   => 0
                                            ],
                                            [
                                                'caption' => 'Blau',
                                                'value'   => 1
                                            ],
                                            [
                                                'caption' => 'Grün',
                                                'value'   => 2
                                            ],
                                            [
                                                'caption' => 'Türkis',
                                                'value'   => 3
                                            ],
                                            [
                                                'caption' => 'Rot',
                                                'value'   => 4
                                            ],
                                            [
                                                'caption' => 'Violett',
                                                'value'   => 5
                                            ],
                                            [
                                                'caption' => 'Gelb',
                                                'value'   => 6
                                            ],
                                            [
                                                'caption' => 'Weiß',
                                                'value'   => 7
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'caption' => 'Modus',
                                    'name'    => 'Mode',
                                    'width'   => '200px',
                                    'add'     => 1,
                                    'edit'    => [
                                        'type'    => 'Select',
                                        'options' => [
                                            [
                                                'caption' => 'Beleuchtung aus',
                                                'value'   => 0
                                            ],
                                            [
                                                'caption' => 'Dauerhaft ein',
                                                'value'   => 1
                                            ],
                                            [
                                                'caption' => 'Langsames Blinken',
                                                'value'   => 2
                                            ],
                                            [
                                                'caption' => 'Mittleres Blinken',
                                                'value'   => 3
                                            ],
                                            [
                                                'caption' => 'Schnelles Blinken',
                                                'value'   => 4
                                            ],
                                            [
                                                'caption' => 'Langsames Blitzen',
                                                'value'   => 5
                                            ],
                                            [
                                                'caption' => 'Mittleres Blitzen',
                                                'value'   => 6
                                            ],
                                            [
                                                'caption' => 'Schnelles Blitzen',
                                                'value'   => 7
                                            ],
                                            [
                                                'caption' => 'Langsames Pulsieren',
                                                'value'   => 8
                                            ],
                                            [
                                                'caption' => 'Mittleres Pulsieren',
                                                'value'   => 9
                                            ],
                                            [
                                                'caption' => 'Schnelles Pulsieren',
                                                'value'   => 10
                                            ],
                                            [
                                                'caption' => 'Vorheriger Wert',
                                                'value'   => 11
                                            ],
                                            [
                                                'caption' => 'Ohne Funktion',
                                                'value'   => 12
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'caption' => 'Helligkeit',
                                    'name'    => 'Brightness',
                                    'width'   => '100px',
                                    'add'     => 100,
                                    'edit'    => [
                                        'type'    => 'NumberSpinner',
                                        'suffix'  => '%',
                                        'minimum' => 0,
                                        'maximum' => 100
                                    ]
                                ],
                                [
                                    'caption' => 'Signalisierung erzwingen',
                                    'name'    => 'ForceExecution',
                                    'width'   => '200px',
                                    'add'     => false,
                                    'edit'    => [
                                        'type' => 'CheckBox'
                                    ]
                                ]
                            ],
                            'values' => ${$statusLED['designation'] . 'TriggerListValues'}
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'Anzahl Auslöser: ' . ${$statusLED['designation'] . 'AmountVariables'}
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => $statusLED['designation'] . 'TriggerListConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ];
        }

        //Automatic update
        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel' . $panelCount++,
                'caption' => 'Aktualisierung',
                'items'   => [
                    [
                        'type'    => 'Label',
                        'caption' => 'Systemstart',
                        'bold'    => true,
                        'italic'  => true
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'ForceExecutionOnSystemStartup',
                        'caption' => 'Aktualisierung erzwingen'
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => "\nSystembetrieb",
                        'bold'    => true,
                        'italic'  => true
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'AutomaticUpdate',
                        'caption' => 'Automatische Aktualisierung'
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'UpdateInterval',
                        'caption' => 'Intervall',
                        'minimum' => 0,
                        'suffix'  => 'Sekunden'
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'UpdateSwitchActuator',
                        'caption' => 'Schaltaktor aktualisieren'
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'UpdateStatusLEDs',
                        'caption' => 'Status LEDs aktualisieren'
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'ForceExecution',
                        'caption' => 'Aktualisierung erzwingen'
                    ]
                ]
            ];

        //Command control
        $id = $this->ReadPropertyInteger('CommandControl');
        $enableButton = false;
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $enableButton = true;
        }

        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel' . $panelCount++,
                'caption' => 'Ablaufsteuerung',
                'items'   => [
                    [
                        'type'  => 'RowLayout',
                        'items' => [
                            [
                                'type'     => 'SelectModule',
                                'name'     => 'CommandControl',
                                'caption'  => 'Instanz',
                                'moduleID' => self::ABLAUFSTEUERUNG_MODULE_GUID,
                                'width'    => '600px',
                                'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "CommandControlConfigurationButton", "ID " . $CommandControl . " konfigurieren", $CommandControl);'
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'caption'  => 'ID ' . $id . ' konfigurieren',
                                'name'     => 'CommandControlConfigurationButton',
                                'visible'  => $enableButton,
                                'objectID' => $id
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Neue Instanz erstellen',
                                'onClick' => self::MODULE_PREFIX . '_Control_CreateCommandControlInstance($id);'
                            ]
                        ]
                    ]
                ]
            ];

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel' . $panelCount++,
            'caption' => 'Deaktivierung',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Schaltaktor',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'         => 'Select',
                    'name'         => 'DeactivationSwitchActuator',
                    'caption'      => 'Schaltvorgang bei Deaktivierung',
                    'options'      => [
                        [
                            'caption' => 'Aus',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Ein',
                            'value'   => 1
                        ],
                        [
                            'caption' => 'Keine Aktion',
                            'value'   => 2
                        ]
                    ]
                ],
                [
                    'type'         => 'Select',
                    'name'         => 'ActivationSwitchActuator',
                    'caption'      => 'Schaltvorgang bei Aktivierung',
                    'options'      => [
                        [
                            'caption' => 'Aus',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Ein',
                            'value'   => 1
                        ],
                        [
                            'caption' => 'Keine Aktion',
                            'value'   => 2
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => "\nStatus LEDs",
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'         => 'HorizontalSlider',
                    'name'         => 'DeactivationBrightnessSlider',
                    'caption'      => 'Helligkeit bei Deaktivierung',
                    'minimum'      => 0,
                    'maximum'      => 100,
                    'displayValue' => true
                ],
                [
                    'type'         => 'HorizontalSlider',
                    'name'         => 'ActivationBrightnessSlider',
                    'caption'      => 'Helligkeit bei Aktivierung',
                    'minimum'      => 0,
                    'maximum'      => 100,
                    'displayValue' => true
                ],
                [
                    'type'    => 'Label',
                    'caption' => "\nAutomatische Deaktivierung",
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseAutomaticDeactivation',
                    'caption' => 'Automatische Deaktivierung'
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'AutomaticDeactivationStartTime',
                    'caption' => 'Startzeit'
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'AutomaticDeactivationEndTime',
                    'caption' => 'Endzeit'
                ]
            ]
        ];

        //Visualisation
        $statusLEDVisualisation = [];
        foreach ($this->statusLEDs as $statusLED) {

            foreach ([
                ['suffix' => 'colorIdent', 'label' => 'Farbe'],
                ['suffix' => 'modeIdent', 'label' => 'Modus'],
                ['suffix' => 'brightnessIdent', 'label' => 'Helligkeit']
            ] as $type) {
                $statusLEDVisualisation[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'Enable' . $statusLED[$type['suffix']],
                    'caption' => $statusLED['caption'] . ' ' . $type['label']
                ];
            }
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel' . $panelCount,
            'caption' => 'Visualisierung',
            'items'   => array_merge(
                [
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'EnableActive',
                        'caption' => 'Aktiv'
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'EnableSwitchActuator',
                        'caption' => 'Schaltaktor'
                    ]
                ],
                $statusLEDVisualisation
            )
        ];

        ########## Actions

        $form['actions'][] =
            [
                'type'    => 'Button',
                'caption' => 'Aktualisierung erzwingen',
                'onClick' => self::MODULE_PREFIX . '_StatusLED_UpdateState($id, true);' . self::MODULE_PREFIX . '_SwitchActuator_UpdateState($id, true);'
            ];

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        $amountReferences = count($references);
        if ($amountReferences == 0) {
            $amountReferences = 3;
        }
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $location = IPS_GetLocation($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID'         => $reference,
                'Name'             => $name,
                'VariableLocation' => $location,
                'rowColor'         => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        $amountMessages = count($messages);
        if ($amountMessages == 0) {
            $amountMessages = 3;
        }
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $location = IPS_GetLocation($id);
                $rowColor = '#C0FFC0'; //light green
            }
            $messageDescription = match ($messageID) {
                [10001] => 'IPS_KERNELSTARTED',
                [10603] => 'VM_UPDATE',
                default => 'keine Bezeichnung',
            };
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'VariableLocation'   => $location,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        //Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Referenzen',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => $amountReferences,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " bearbeiten", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Nachrichten',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => $amountMessages,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " bearbeiten", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Dummy info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => 'HmIP-WRC6-230 wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => 'HmIP-WRC6-230 ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => 'HmIP-WRC6-230 wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => 'HmIP-WRC6-230 ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}