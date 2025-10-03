<?php

namespace BookneticApp\Backend\Appointments\Helpers;

/**
 * Bele olmasinda sebeb mixed tipi PHP 8-de geldi. Ashagi versiyalarda yoxdu.
 * JsonSerializable ise sennen teleb edir ki, ora mixed atasan 8ci versiyada.
 * Biz ora mixed atanda ashag versiya error verir ki, tanimiram bu tipi;
 * Atamayanda 8 error verir ki, mixed atmalisan. Ona gore fix usulu yalniz budu.
 */
if (version_compare(PHP_VERSION, '8.0.0', '<'))
{
    trait WeeklyTimeSheetObjectJsonSerialize
    {
        public function jsonSerialize()
        {
            return $this->toArr();
        }
    }
}
else
{
    trait WeeklyTimeSheetObjectJsonSerialize
    {
        public function jsonSerialize() : mixed
        {
            return $this->toArr();
        }
    }
}