<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            $this->createHasKeyFunction(),
            $this->createGetFirstFunction(),
            $this->createIsRecoverableFunction(),
            $this->createContainsFunction(),
            $this->createUniqIdFunction(),
            $this->createSubStrFunction(),
            $this->createDefaultTo(),
            $this->createNumberFormat(),
            $this->createMd5Function(),
            $this->createCountFunction(),
            $this->createSliceFunction(),
            $this->createExplodeFunction(),
            $this->createImplodeFunction(),
            $this->createRemoveNewLinesFunction(),
            $this->createStrtoupperFunction(),
            $this->createGetKeyFunction(),
            $this->createGetValueFunction(),
        ];
    }

    /**
     * @return ExpressionFunction
     */
    protected function createContainsFunction()
    {
        return new ExpressionFunction(
            'contains',
            function ($string, $search) {
                return sprintf('( strpos(%s,%s) !== false )', $string, $search);
            },
            function ($arguments, $string, $search) {
                if (!is_string($string) || !is_string($search)) {
                    throw new \RuntimeException('Both arguments passed to "contains" should be strings.');
                }

                return false !== strpos($string, $search);
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createDefaultTo()
    {
        return new ExpressionFunction(
            'defaultTo',
            function ($value, $defaultValue) {
                return sprintf('(%s !== null ? %s : %s)', $value, $value, $defaultValue);
            },
            function ($arguments, $value, $defaultValue) {
                return null !== $value ? $value : $defaultValue;
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createHasKeyFunction()
    {
        return new ExpressionFunction(
            'hasKey',
            function ($key, $array) {
                return sprintf('(array_key_exists(%s,%s))', $key, $array);
            },
            function ($arguments, $key, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('Second argument passed to "hasKey" should be an array.');
                }

                return array_key_exists($key, $array);
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createGetFirstFunction()
    {
        return new ExpressionFunction(
            'getFirst',
            function ($array) {
                return sprintf('reset(%s)', $array);
            },
            function ($arguments, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('First argument passed to "getFirst" should be an array.');
                }

                return reset($array);
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createIsRecoverableFunction()
    {
        return new ExpressionFunction(
            'isRecoverable',
            function ($object) {
                return sprintf('(%s instanceof \Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface)', $object);
            },
            function ($arguments, $object) {
                if (!is_object($object) || !($object instanceof \Exception)) {
                    throw new \RuntimeException('First argument should be an exception');
                }

                return $object instanceof RecoverableExceptionInterface;
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createUniqIdFunction()
    {
        return new ExpressionFunction(
            'uniqid',
            function () {
                return '( uniqid() )';
            },
            function ($arguments) {
                return uniqid();
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createSubStrFunction()
    {
        return new ExpressionFunction(
            'substr',
            function ($string, $start, $length) {
                return sprintf('( is_string(%1$1) && substr(%1$s, %2$s, %3$s) : %1$s ) === false ? "" : substr(%1$s, %2$s, %3$s) : %1$s ) )', $string, $start, $length);
            },
            function ($arguments, $string, $start, $length) {
                if (!is_string($string)) {
                    return '';
                }

                $sub_string = substr($string, $start, $length);
                if (false === $sub_string) {
                    return '';
                }

                return $sub_string;
            }
        );
    }

    /**
     * Exposes php number_format.
     *
     * string numberFormat ( float $number , int $decimals = 0 , string $dec_point = "." , string $thousands_sep = "," )
     * returns null if null passed
     *
     * @return ExpressionFunction
     */
    protected function createNumberFormat()
    {
        return new ExpressionFunction(
            'numberFormat',
            function ($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',') {
                return sprintf('( %1$s !== null ? number_format(%1$s, %2$s, %3$s, %4$s)) : null', $number, $decimals, $dec_point, $thousands_sep);
            },
            function ($arguments, $number, $decimals = 0, $dec_point = '.', $thousands_sep = ',') {
                if (null === $number) {
                    return null;
                }

                return number_format($number, $decimals, $dec_point, $thousands_sep);
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createMd5Function()
    {
        return new ExpressionFunction(
            'md5',
            function ($string) {
                return sprintf('md5(%s)', $string);
            },
            function ($arguments, $string) {
                return md5($string);
            }
        );
    }

    /**
     * Return the number of elements that an array has.
     *
     * @return ExpressionFunction
     */
    protected function createCountFunction()
    {
        return new ExpressionFunction(
            'count',
            function ($string) {
                return sprintf('count(%s)', $string);
            },
            function ($arguments, $string) {
                return count($string);
            }
        );
    }

    /**
     * Returns the sequence of elements from an array based on start and length parameters.
     *
     * @return ExpressionFunction
     */
    protected function createSliceFunction()
    {
        return new ExpressionFunction(
            'slice',
            function ($array, $start, $length = null, $preserveKeys = false) {
                return sprintf('array_slice(%s, %s, %s, %s)', $array, $start, $length, $preserveKeys);
            },
            function ($arguments, $array, $start, $length = null, $preserveKeys = false) {
                return array_slice($array, $start, $length, $preserveKeys);
            }
        );
    }

    /**
     * Explode a string into an array.
     *
     * @return ExpressionFunction
     */
    protected function createExplodeFunction()
    {
        return new ExpressionFunction(
            'explode',
            function ($delimiter, $string) {
                return sprintf('explode(%s,%s)', $delimiter, $string);
            },
            function ($arguments, $delimiter, $string) {
                return explode($delimiter, $string);
            }
        );
    }

    /**
     * Implode an array to a string.
     *
     * @return ExpressionFunction
     */
    protected function createImplodeFunction()
    {
        return new ExpressionFunction(
            'implode',
            function ($glue, $pieces) {
                return sprintf('implode(%s,%s)', $glue, $pieces);
            },
            function ($arguments, $glue, $pieces) {
                return implode($glue, $pieces);
            }
        );
    }

    /**
     * Remove any new lines in $string, like \n, \r\n or \r.
     *
     * @return ExpressionFunction
     */
    protected function createRemoveNewLinesFunction()
    {
        return new ExpressionFunction(
            'removeNewLines',
            function ($string) {
                return sprintf('removeNewLines(%s)', $string);
            },
            function ($arguments, $string) {
                return preg_replace('/[\r\n]+/', ' ', trim($string));
            }
        );
    }

    /**
     * Returns the uppercased string.
     *
     * @return ExpressionFunction
     */
    protected function createStrtoupperFunction()
    {
        return new ExpressionFunction(
            'strtoupper',
            function ($string) {
                return sprintf('strtoupper(%s)', $string);
            },
            function ($arguments, $string) {
                return strtoupper($string);
            }
        );
    }

    /**
     * Returns the keys from an array.
     *
     * @return ExpressionFunction
     */
    protected function createGetKeyFunction()
    {
        return new ExpressionFunction(
            'getKey',
            function ($array) {
                return sprintf('getKey(%s)', $array);
            },
            function ($arguments, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('Argument passed to "getKey" should be an array.');
                }

                if (!isset($array['key'])) {
                    throw new \RuntimeException('The argument passed to "getKey" should have a index called "key"');
                }

                return $array['key'];
            }
        );
    }

    /**
     * Returns the key value from a disassociate array.
     * $array should be as ['key' => 123, 'value' => 222].
     *
     * @return ExpressionFunction
     */
    protected function createGetValueFunction()
    {
        return new ExpressionFunction(
            'getValue',
            function ($array) {
                return sprintf('getValue(%s)', $array);
            },
            function ($arguments, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('Argument passed to "getKey" should be an array.');
                }

                if (!isset($array['value'])) {
                    throw new \RuntimeException('The argument passed to "getKey" should have a index called "value"');
                }

                return $array['value'];
            }
        );
    }
}
