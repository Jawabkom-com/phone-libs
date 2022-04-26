<?php

namespace Jawabkom\Library;

use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;

class PhoneCleaner
{
    public function parse(string $phoneNumber, array $possibleCountryCodes = []): array
    {
        $cleanPhoneNumber = $this->cleanPhone($phoneNumber);
        $parse = [
            'phone' => $cleanPhoneNumber,
            'is_valid' => false,
            'country_code' => null
        ];

        try {
            $oPhone = $this->parseNormalizedPhoneNumber($cleanPhoneNumber);
        } catch (\Exception $e) {
            try {
                $oPhone = $this->parsePhoneUsingPossibleCountries($cleanPhoneNumber, $possibleCountryCodes);
            } catch (\Exception $e) {
                try {
                    $oPhone = $this->parsePhoneUsingPlusPrefix($cleanPhoneNumber);
                } catch (\Exception $e) {
                    return $parse;
                }
            }
        }
        $parse['country_code'] = $oPhone->getRegionCode();
        $parse['is_valid'] = true;
        $parse['phone'] = $oPhone->format(PhoneNumberFormat::E164);
        return $parse;
    }

    protected function parseNormalizedPhoneNumber($phoneNumber)
    {
        $phoneObj = PhoneNumber::parse($phoneNumber);
        if (!$phoneObj->isValidNumber()) {
            throw new \Exception();
        }
        return $phoneObj;
    }

    protected function parsePhoneUsingPossibleCountries($phoneNumber, array $possibleCountryCodes = [])
    {
        foreach ($possibleCountryCodes as $countryCode) {
            try {
                $phoneObj = PhoneNumber::parse($phoneNumber, $countryCode);
                if ($phoneObj->isValidNumber()) {
                    return $phoneObj;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }
        throw new \Exception();
    }

    protected function parsePhoneUsingPlusPrefix($phoneNumber)
    {
        $phoneObj = PhoneNumber::parse("+$phoneNumber");
        if (!$phoneObj->isValidNumber()) {
            throw new \Exception();
        }
        return $phoneObj;
    }

    protected function fixArabicNumbers($value)
    {
        $arabic_eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $arabic_western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($arabic_eastern, $arabic_western, $value);
    }

    protected function cleanPhone($phone)
    {
        $phone = trim(str_replace(['(', ')', '-', ' ', '/', '\\', '.', '_'], '', $phone));
        $phone = $this->fixArabicNumbers($phone);
        if (strpos($phone, '00') === 0)
            $phone = substr_replace($phone, '+', 0, 2);
        return $phone;
    }

}