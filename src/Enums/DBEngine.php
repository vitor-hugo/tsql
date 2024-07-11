<?php declare(strict_types=1);

namespace Torugo\Sql\Enums;

enum DBEngine
{
    case MariaDB;
    case MySql;
    case Postgres;
    case SqlServer;
}
