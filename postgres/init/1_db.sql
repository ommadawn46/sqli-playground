CREATE DATABASE sqli;

\c sqli;

CREATE TABLE IF NOT EXISTS users(
  id integer NOT NULL,
  username varchar(32) NOT NULL,
  password varchar(32) NOT NULL
);

INSERT INTO users VALUES (1, 'admin', 'p4ssw0rd');
