<?php declare(strict_types=1);

namespace Torugo\Sql\Enums;

enum DBEngine
{
    case Aurora;
    case MariaDB;
    case MySql;
    case Postgress;
    case SqlServer;
}
