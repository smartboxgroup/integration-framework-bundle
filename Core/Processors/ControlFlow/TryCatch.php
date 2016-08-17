<?php
namespace Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ConditionalClause;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;

class TryCatch extends Processor{

    use UsesEvaluator;

    /** @var  ConditionalClause[] */
    protected $catches;

    /** @var  Itinerary */
    protected $finallyItinerary;

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ConditionalClause[]
     */
    public function getCatches()
    {
        return $this->catches;
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ConditionalClause[] $catches
     */
    public function setCatches($catches)
    {
        $this->catches = $catches;
    }

    /**
     * @param string|null    $condition
     * @param Itinerary|null $itinerary
     */
    public function addCatch($condition = null, Itinerary $itinerary = null)
    {
        $this->catches[] = new ConditionalClause($condition, $itinerary);
    }

    /**
     * @return Itinerary
     */
    public function getFinallyItinerary()
    {
        return $this->finallyItinerary;
    }

    /**
     * @param Itinerary $finallyItinerary
     */
    public function setFinallyItinerary($finallyItinerary)
    {
        $this->finallyItinerary = $finallyItinerary;
    }

    /**
     * @param Exchange $exchange
     * @param SerializableArray $processingContext
     * @throws \Exception
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $itinerary = $exchange->getItinerary();

        if($itinerary->getCount() > 0){
            $exchangeBackup = clone $exchange;

            // Take the next processor
            $processor = $itinerary->shiftProcessor();

            // Prepend this processor
            $itinerary->unShiftProcessor($this);

            try{
                $processor->process($exchange);
            }catch (ProcessingException $exception){
                $originalException = $exception->getOriginalException();

                // We try to catch the exception
                foreach($this->catches as $catch){
                    if($this->evaluator->evaluateWithExchange($catch->getCondition(),$exchange, $originalException)){
                        $exchange->setIn($exchangeBackup->getIn());
                        $exchange->setOut(null);
                        $exchange->setItinerary($catch->getItinerary());
                        if($this->finallyItinerary) {
                            $exchange->getItinerary()->append($this->finallyItinerary);
                        }
                        return;
                    }
                }

                // If it's not catch, we just let go, if the exchange is retried, we will come to this processor again
                throw $originalException;
            }
        }else if($this->finallyItinerary){
            $exchange->getItinerary()->prepend($this->finallyItinerary);
        }
    }
}