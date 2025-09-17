<?php
declare(strict_types=1);

namespace app\enums;

enum FilterInputRule: string
{
    case ArrayInt = 'array:int';
    case ArrayString = 'array:string';
    case Avatar = '/^\p{So}$/u';
    case Bool = 'bool';
    case Content = '/^.{1,500}$/s';
    case DateTime = '/^(\d{4}(-\d{2}(-\d{2})?)?( \d{2}:\d{2}(:\d{2})?)?|(\d{2}:\d{2}(:\d{2})?))$/';
    case Email = '/^[a-z0-9._%+\-]*(@[a-z0-9.\-]*)?(\.[a-z]*)?$/i';
    case Float = 'float';
    case Html = '/^\s*(<[^>]+>.*?)+\s*$/is';
    case HtmlSafeName = '/^[A-Za-zÀ-ÖØ-öø-ÿ0-9\'\-\.\s_,()&]{2,100}$/u';
    case HtmlSafeText = '/^(.*?<\/?(b|i|u|strong|em|br|span)[^>]*>.*?|[^<>]*)*$/isu';
    case Int = 'int';
    case Integer = '/^\d+$/';
    case Json = '/^\s*(\{.*\}|\[.*\])\s*$/s';
    case Location = '/^-?\d{1,2}\.\d+,-?\d{1,3}\.\d+$/';
    case Password = '/^.{6,30}$/';
    case PersonName = '/^(?![ \-])[A-Za-zÀ-ÖØ-öø-ÿ\- ]{1,100}(?<![ \-])$/';
    case Phone = '/^\+?[0-9\s().\-]{6,20}$/';
    case Uri = '/^[a-zA-Z0-9\-._~\/]{1,255}$/';
    case Token = '/^[a-fA-F0-9]{32}$/';
    case CheckboxMatrix = 'checkbox:matrix';
}
