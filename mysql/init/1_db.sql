CREATE TABLE IF NOT EXISTS sqli.users(
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL
);

INSERT INTO sqli.users VALUES (1, 'admin', 'p4ssw0rd');
