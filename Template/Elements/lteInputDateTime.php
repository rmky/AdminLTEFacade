<?php
namespace exface\AdminLteTemplate\Template\Elements;

class lteInputDateTime extends lteInputDate
{
    // Vorsicht wenn neben dem Datum auch die Zeit uebergeben werden soll. In welcher
    // Zeitzone befindet sich der Client und der Server. In welcher Zeitzone erwartet der
    // Server die uebergebene Zeit? new Date(...) und date.toString arbeiten immer mit
    // der Zeitzone des Clients. Der Bootstrap Datepicker erwartet die uebergebenen Dates
    // in der UTC-Zeitzone und gibt auch entsprechende Dates zurueck. Dates in UTC-Zeit
    // koennen z.B. mit new Date(Date.UTC(yyyy, MM, dd)) erstellt werden.
}