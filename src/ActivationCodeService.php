<?php

namespace Crow\LaravelActivationCode;

use Carbon\Carbon;
use Exception;
use Crow\LaravelActivationCode\Model\ActivationCode;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ActivationCodeService
{
    /**
     * Default ttl for activation codes
     */
    public const DEFAULT_DATE_VARIABLE = '1h';
    public const DATE_VARIABLE_SIMILAR = [
        '' => 'addSeconds',
        'm' => 'addMinutes',
        'h' => 'addHours',
        'd' => 'addDays',
    ];
    /**
     * Default max attempt get code before remove this code
     */
    public const DEFAULT_MAX_ATTEMPT = 5;
    public const DEFAULT_CODE_LENGTH = 20;
    public const GENERATE_CODE_MODE_ALPHABET = 1;
    public const GENERATE_CODE_MODE_ALPHABET_LOWER = 2;
    public const GENERATE_CODE_MODE_ALPHABET_UPPER = 3;
    public const GENERATE_CODE_MODE_NUMBER = 4;
    public const GENERATE_CODE_MODE_ALL = 5;
    public const MODE_SMS = 'sms';

    private string $model;
    private ?int $generateCodeMode = null;
    private ?string $mode = null;
    private ?int $codeLength = null;
    private ?int $maxAttempt = null;
    private ?string $codeTTL = null;

    public function __construct()
    {
        $this->model = Config::get('activation_code.model');
    }

    public function setGenerateCodeMode(?int $generateCodeMode = null): self
    {
        $this->generateCodeMode = $generateCodeMode;
        return $this;
    }

    public function setMode(?string $mode = null): self
    {
        $this->mode = $mode;
        return $this;
    }

    public function setCodeLength(?int $codeLength): self
    {
        $this->codeLength = $codeLength;
        return $this;
    }

    public function setMaxAttempt(?int $maxAttempt): self
    {
        $this->maxAttempt = $maxAttempt;
        return $this;
    }

    public function setCodeTTL(?string $codeTTL): self
    {
        $this->codeTTL = $codeTTL;
        return $this;
    }

    public function get(
        ?string $receiver,
        ?string $code,
        ?string $type,
        bool $exception = true,
        bool $notCheckAttempt = false
    ): ?ActivationCode {
        $activationCodes = $this->getModel($receiver, $type);

        if (!$activationCodes->count()) {
            if ($exception) {
                throw ActivationCodeServiceException::notFound();
            } else {
                return null;
            }
        }

        /** @var ActivationCode $activationCode */
        $activationCode = $activationCodes->first();

        if ($code != $activationCode->code) {
            if ($notCheckAttempt) {
                if ($exception) {
                    throw ActivationCodeServiceException::notValid();
                } else {
                    return null;
                }
            }
            $max = $this->getMaxAttempt();
            $activationCode->attempt++;
            $activationCode->save();
            if ($activationCode->attempt >= $max) {
                $this->delete($activationCode);
            }
            if ($exception) {
                throw ActivationCodeServiceException::notValidCode($activationCode->attempt, $max);
            } else {
                return null;
            }
        }

        return $activationCode;
    }

    public function getByCode(
        string $code,
        string $type,
        bool $exception = true
    ): ?ActivationCode {
        $activationCodes = $this->getModelByCode($code, $type);

        if (!$activationCodes->count()) {
            if ($exception) {
                throw ActivationCodeServiceException::notFound();
            } else {
                return null;
            }
        }

        return $activationCodes->first();
    }

    public function delete(ActivationCode $activationCode): void
    {
        try {
            $activationCode->delete();
        } catch (Exception $e) {
            throw ActivationCodeServiceException::message($e->getMessage());
        }
    }

    public function make(?string $receiver, ?string $type, ?int $id = null): ActivationCode
    {
        $activationCodes = $this->getModel($receiver, $type, $id);
        foreach ($activationCodes as $activationCode) {
            $this->delete($activationCode);
        }
        $activationCode = new $this->model();
        $activationCode->receiver = $receiver;
        $activationCode->code = $this->generateCode();
        $activationCode->type = $type;
        $activationCode->record_id = $id;
        $activationCode->expires_at = $this->generateExpiresDateTime();
        $activationCode->save();

        return $activationCode;
    }

    public function generateCode(): string
    {
        $symbols = collect();
        switch ($this->getGenerateCodeMode()) {
            case self::GENERATE_CODE_MODE_ALPHABET:
                $symbols = $symbols->merge(range('a', 'z'))->merge(range('A', 'Z'));
                break;
            case self::GENERATE_CODE_MODE_ALPHABET_LOWER:
                $symbols = $symbols->merge(range('a', 'z'));
                break;
            case self::GENERATE_CODE_MODE_ALPHABET_UPPER:
                $symbols = $symbols->merge(range('A', 'Z'));
                break;
            case self::GENERATE_CODE_MODE_NUMBER:
                $symbols = $symbols->merge(range('0', '9'));
                break;
            default:
                $symbols = $symbols->merge(range('a', 'z'))->merge(range('A', 'Z'))->merge(range('0', '9'));
                break;
        }

        $code = '';
        for ($i = 1; $i <= $this->getCodeLength(); $i++) {
            $code .= $symbols->random();
        }

        return $code;
    }

    public function reset(): void
    {
        $this->generateCodeMode = null;
        $this->mode = null;
        $this->codeLength = null;
        $this->maxAttempt = null;
        $this->codeTTL = null;
    }

    private function generateExpiresDateTime(): Carbon
    {
        $time = $this->getCodeTTL();

        return $this->parseVariableDateToCarbon($time);
    }

    private function parseVariableDateToCarbon(?string $date): Carbon
    {
        $carbon = now()->addHour();
        if (!$date) {
            return $carbon;
        }

        preg_match('/^([0-9]+)(.*)$/', $date, $matches);
        $matches = collect($matches);
        if ($matches->count() == 3) {
            $method = isset(self::DATE_VARIABLE_SIMILAR[Str::lower($matches->get(2))]) ?
                self::DATE_VARIABLE_SIMILAR[Str::lower($matches->get(2))] : 'addSeconds';
            $carbon = now()->$method((int)$matches->get(1));
        }
        return $carbon;
    }

    private function getModel(?string $receiver, ?string $type, ?int $recordId = null): EloquentCollection
    {
        $query = $this->model::query();
        if ($receiver) {
            $query->where('receiver', '=', $receiver);
        }
        if ($type) {
            $query->where('type', '=', $type);
        }
        if ($recordId) {
            $query->where('record_id', '=', $recordId);
        }
        $query->where('expires_at', '>', now()->toDateTimeString());

        return $query->get();
    }

    /**
     * @param string $code
     * @param string $type
     * @return ActivationCode[]|EloquentCollection
     */
    private function getModelByCode(string $code, string $type): EloquentCollection|array
    {
        $query = $this->model::query();
        $query->where('code', '=', $code);
        $query->where('type', '=', $type);
        $query->where('expires_at', '>', now()->toDateTimeString());

        return $query->get();
    }

    private function config(?string $key, $default = null): mixed
    {
        $value = Config::get($key);

        return $value ?? $default;
    }

    private function getMaxAttempt(): int
    {
        if ($this->maxAttempt) {
            return $this->maxAttempt;
        }

        return $this->config(
            sprintf(
                'activation_code.%s.max_attempt',
                $this->mode == self::MODE_SMS ? 'sms' : 'default'
            ),
            self::DEFAULT_MAX_ATTEMPT
        );
    }

    private function getGenerateCodeMode(): ?int
    {
        if ($this->generateCodeMode) {
            return $this->generateCodeMode;
        }

        return $this->config(
            sprintf(
                'activation_code.%s.code_generate_mode',
                $this->mode == self::MODE_SMS ? 'sms' : 'default'
            )
        );
    }

    private function getCodeLength(): ?int
    {
        if ($this->codeLength) {
            return $this->codeLength;
        }

        return $this->config(
            sprintf(
                'activation_code.%s.code_length',
                $this->mode == self::MODE_SMS ? 'sms' : 'default'
            ),
            self::DEFAULT_CODE_LENGTH
        );
    }

    private function getCodeTTL(): ?string
    {
        if ($this->codeTTL) {
            return $this->codeTTL;
        }

        return $this->config(
            sprintf(
                'activation_code.%s.code_ttl',
                $this->mode == self::MODE_SMS ? 'sms' : 'default'
            ),
            self::DEFAULT_DATE_VARIABLE
        );
    }
}
