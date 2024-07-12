use TestDB;

CREATE TABLE IF NOT EXISTS TestTable
(
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(50) NOT NULL,
    `age` int(11) NOT NULL,
    PRIMARY KEY (`id`)
)
