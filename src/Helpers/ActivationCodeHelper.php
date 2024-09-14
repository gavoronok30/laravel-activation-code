<?php

namespace Crow\LaravelActivationCode\Helpers;

use Illuminate\Support\Facades\Facade;
use Crow\LaravelActivationCode\ActivationCodeService;
use Crow\LaravelActivationCode\Model\ActivationCode;

/**
 * @method static ActivationCodeService setGenerateCodeMode(?int $generateCodeMode = null)
 * @method static ActivationCodeService setMode(?string $mode = null)
 * @method static ActivationCodeService setCodeLength(?int $codeLength)
 * @method static ActivationCodeService setMaxAttempt(?int $maxAttempt)
 * @method static ActivationCodeService setCodeTTL(?string $codeTTL)
 * @method static ActivationCode make(?string $receiver, ?string $type, ?int $id = null)
 * @method static string generateCode()
 * @method static ActivationCode|null get(?string $receiver, ?string $code, ?string $type, bool $exception = true, bool $notCheckAttempt = false)
 * @method static ActivationCode|null getByCode(string $code, string $type, bool $exception = true)
 * @method static void delete(ActivationCode $activationCode)
 * @method static void reset()
 */
class ActivationCodeHelper extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ActivationCodeHelperHandler::class;
    }
}
