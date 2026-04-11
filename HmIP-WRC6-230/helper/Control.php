<?php

declare(strict_types=1);

trait Control
{
    ######### Public Methods  ##########

    /**
     * Toggles the main switch between active and inactive.
     *
     * @param bool $State
     * false =  deactivate (maintenance mode,
     * true =   activate
     *
     * @return void
     * @throws Exception
     */
    public function ToggleActive(bool $State): void
    {
        if (!$State) {
            //Set all status LEDs off
            foreach ($this->statusLEDs as $led) {
                $this->StatusLED_SetBrightness($led['channel'], $this->ReadPropertyInteger('DeactivationBrightnessSlider'));
            }
            $this->SetValue('Active', false);
        }
        else {
            $currentState = $this->GetValue('Active');
            $this->SetValue('Active', true);
            if (!$currentState) {
                $this->SwitchActuator_UpdateState(true);
                $this->StatusLED_UpdateState(true);
            }
        }
    }

    /**
     * Executes the automatic update.
     *
     * @return void
     * @throws Exception
     */
    public function AutomaticUpdate(): void
    {
        $this->SwitchActuator_UpdateState($this->ReadPropertyBoolean('ForceExecution'));
        $this->StatusLED_UpdateState($this->ReadPropertyBoolean('ForceExecution'));
    }

    /**
     * Starts the automatic deactivation.
     *
     * @return void
     * @throws Exception
     */
    public function StartAutomaticDeactivation(): void
    {
        $this->ToggleActive(false);
        $this->SetAutomaticDeactivationTimer();
    }

    /**
     * Stops the automatic deactivation.
     *
     * @return void
     * @throws Exception
     */
    public function StopAutomaticDeactivation(): void
    {
        $this->SetAutomaticDeactivationTimer();
        $this->ToggleActive(true);
    }

    ######### Protected Methods  ##########

    /**
     * Attempts to set the semaphore and repeats this up to multiple times.
     *
     * @param $Name
     * @return bool
     * @throws Exception
     */
    protected function LockSemaphore($Name): bool
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

    /**
     * Leaves the semaphore.
     *
     * @param $Name
     * @return void
     */
    protected function UnlockSemaphore($Name): void
    {
        @IPS_SemaphoreLeave(__CLASS__ . '.' . $this->InstanceID . '.' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore ' . $Name . ' unlocked', 0);
    }

    ######### Private Methods  ##########

    /**
     * Sets the timer for the automatic deactivation.
     *
     * @return void
     * @throws Exception
     */
    private function SetAutomaticDeactivationTimer(): void
    {
        $use = $this->ReadPropertyBoolean('UseAutomaticDeactivation');
        //Start
        $milliseconds = 0;
        if ($use) {
            $milliseconds = $this->GetInterval('AutomaticDeactivationStartTime');
        }
        $this->SetTimerInterval('StartAutomaticDeactivation', $milliseconds);
        //End
        $milliseconds = 0;
        if ($use) {
            $milliseconds = $this->GetInterval('AutomaticDeactivationEndTime');
        }
        $this->SetTimerInterval('StopAutomaticDeactivation', $milliseconds);
    }

    /**
     * Gets the interval for a timer.
     *
     * @param string $TimerName
     * @return int
     * @throws Exception
     */
    private function GetInterval(string $TimerName): int
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

    /**
     * Checks the status of the automatic deactivation timer.
     *
     * @return bool
     * false =  timer is active,
     * true =   timer is inactive
     * @throws Exception
     */
    private function CheckAutomaticDeactivationTimer(): bool
    {
        if (!$this->ReadPropertyBoolean('UseAutomaticDeactivation')) {
            return false;
        }
        $start = $this->GetTimerInterval('StartAutomaticDeactivation');
        $stop = $this->GetTimerInterval('StopAutomaticDeactivation');
        if ($start > $stop) {
            //Deactivation timer is active, must be toggled to inactive
            $this->ToggleActive(false);
            return true;
        } else {
            //Deactivation timer is inactive, must be toggled to active
            $this->ToggleActive(true);
            return false;
        }
    }
}