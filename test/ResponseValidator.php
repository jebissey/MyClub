<?php

class ResponseValidator implements ResponseValidatorInterface
{
    public function validate(string $actualResponse, string $expectedResponse): ValidationResult
    {
        // Validation par regex
        if (str_starts_with($expectedResponse, 'regex:')) {
            $pattern = substr($expectedResponse, 6);
            $isValid = preg_match($pattern, $actualResponse) === 1;
            return new ValidationResult($isValid, $isValid ? '' : 'Pattern regex non trouvé');
        }

        // Validation JSON
        if (str_starts_with($expectedResponse, 'json:')) {
            $expectedJson = substr($expectedResponse, 5);
            $expected = json_decode($expectedJson, true);
            $actual = json_decode($actualResponse, true);
            $isValid = $expected === $actual;
            return new ValidationResult($isValid, $isValid ? '' : 'JSON différent');
        }

        // Validation texte simple
        $isValid = trim($actualResponse) === trim($expectedResponse);
        return new ValidationResult($isValid, $isValid ? '' : 'Contenu différent');
    }
}
