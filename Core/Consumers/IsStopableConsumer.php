<?php
namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;


trait IsStopableConsumer {

    /** @var bool */
    protected $stop = false;

    /** @var int  */
    protected $expirationCount = -1;

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
     * Checks if it should stop at the current iteration.
     *
     * @return bool
     */
    protected function shouldStop()
    {
        pcntl_signal_dispatch();
        pcntl_signal(SIGTERM, function () {
            $this->stop();
        });

        return $this->stop || 0 === $this->expirationCount;
    }
}