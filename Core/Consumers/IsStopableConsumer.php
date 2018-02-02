<?php
namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;


trait IsStopableConsumer {

    /** @var bool */
    protected $stop = false;

    /** @var int  */
    protected $expirationCount = -1;

    /** @var int  */
    protected $expirationTime = 100 * 365 * 24 * 60 * 60;

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
        $this->stop = true;
    }

    /**
     * {@inheritDoc}
     */
    public function setExpirationCount($count)
    {
        $this->expirationCount = $count;
    }

    /**
     * {@inheritDoc}
     */
    public function setExpirationTime($time)
    {
        $this->expirationTime = 100 * 365 * 24 * 60 * 60; // 100 years after Unix Epoch
        if ($time > 0) {
            $this->expirationTime = time() + $time; // $time seconds after now
        }

    }

    /**
     * Checks if it should stop at the current iteration.
     *
     * @return bool
     */
    protected function shouldStop()
    {
        pcntl_signal_dispatch();
        return $this->stop || $this->expirationCount == 0 || time() > $this->expirationTime;
    }
}