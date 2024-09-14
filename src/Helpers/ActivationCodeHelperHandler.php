<?php

namespace Crow\LaravelActivationCode\Helpers;

use Crow\LaravelActivationCode\ActivationCodeService;
use Illuminate\Support\Collection;
use ReflectionMethod;

class ActivationCodeHelperHandler
{
    private Collection $methods;

    public function __construct(
        private ActivationCodeService $service
    ) {
        $this->methods = collect();
    }

    public function __call(string $methodName, array $arguments): mixed
    {
        if (!$this->methods->has($methodName)) {
            $this->methods->put(
                $methodName,
                (new ReflectionMethod($this->service, $methodName))->isPublic()
            );
        }

        if ($this->methods->get($methodName)) {
            return $this->service->$methodName(...$arguments);
        }

        return null;
    }
}
