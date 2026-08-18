# ローカル開発環境

## 位置づけ

Docker Composeを使用し、Squash Platformのローカル開発環境を構築する。
本番環境はお名前.com レンタルサーバー RSプランであり、Dockerコンテナをそのまま本番へ配置することは前提としない。

## 構成

| サービス | 用途 | バージョン・状態 |
|---|---|---|
| `app` | Apache、PHP、Composer、CakePHP実行環境 | PHP 8.5、CakePHP 5.4（決定済み） |
| `db` | ローカルデータベース | MySQL 8.4 LTS（ローカル暫定、2026-08-18確認） |
| `node` | Vue.js 3.5、Bootstrap 5.3、Vite 8の依存管理・ビルド用 | Node.js 24（必要時のみ起動） |

本番MySQLのバージョン、照合順序、TLS接続条件は接続試験後に確定する。確認結果によってローカルMySQLのバージョンと照合順序を合わせる。

## 前提

- Docker Desktopが起動していること
- Docker Compose v2以降を使用できること
- 使用するポートが空いていること（初期値: Web `8080`、MySQL `3306`）

## 初回準備

PowerShellでリポジトリ直下へ移動し、ローカル設定を作成する。

```powershell
Copy-Item .env.example .env
```

`.env`はGit管理外である。開発用パスワードを変更する場合は、このファイルだけを編集する。その後、バックエンドとフロントエンドの依存関係をインストールする。

```powershell
docker compose run --rm app composer install
docker compose --profile tools run --rm --no-deps node npm ci
docker compose --profile tools run --rm --no-deps node npm run build
```

## 基本操作

```powershell
# WebとMySQLを起動
docker compose up -d --build

# 状態を確認
docker compose ps

# ログを確認
docker compose logs -f app db

# CakePHPコマンドを実行
docker compose exec app bin/cake

# Composerを実行
docker compose exec app composer install

# Node.js補助コンテナも起動
docker compose --profile tools up -d

# Vue.js・Bootstrapを変更中、自動的に再ビルド
docker compose --profile tools exec node npm run dev

# フロントエンドを1回ビルド
docker compose --profile tools run --rm --no-deps node npm run build

# 停止
docker compose down
```

Web画面は `http://localhost:8080` で確認する。`.env`の`APP_PORT`を変更した場合は、そのポートを使用する。

## データベース接続

コンテナ間では、CakePHPからホスト名`db`、ポート`3306`で接続する。Composeは`DATABASE_URL`を`app`コンテナへ渡す。

ホストPCのデータベースクライアントから接続する場合は、初期状態で次を使用する。

| 項目 | 値 |
|---|---|
| ホスト | `127.0.0.1` |
| ポート | `3306`（`.env`の`MYSQL_PORT`） |
| データベース | `snick_platform` |
| ユーザー | `snick` |
| パスワード | `.env`の`MYSQL_PASSWORD` |

MySQLのデータはDockerの名前付きボリューム`mysql-data`へ保持する。`docker compose down`では削除されない。`docker compose down -v`はローカルデータを削除するため、意図して初期化する場合だけ使用する。

## 秘密情報

- 本番データベース、SMTP、SSH等の認証情報を`.env.example`へ記載しない。
- 本番用設定をローカルDocker環境へ流用しない。
- `.env`、CakePHPの環境別ローカル設定、鍵ファイルをGitへコミットしない。
