<?php

namespace app\enums;

enum WeekdayFormat: string
{
    case Full = 'EEEE';      // Full name: "Monday"
    case Short = 'EEE';      // Abbreviated: "Mon"
    case Narrow = 'EEEEE';   // Very short: "M"
    case Minimal = 'EEEEEE'; // Often 2-letter form: "Mo"
}