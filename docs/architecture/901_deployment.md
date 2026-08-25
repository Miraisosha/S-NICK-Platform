# デプロイ

## 位置づけ

本書は、Squash Platformをお名前.com レンタルサーバー RSプランへ配備する方針を管理する。ワークフローは[`.github/workflows/deploy-production.yml`](../../.github/workflows/deploy-production.yml)で管理する。

## 目標構成

- APIは`api.squash-platform.com`へ配備する。
- FRONTは利用者区分ごとのVite成果物を、それぞれ対応する`*.squash-platform.com`へ配備する。
- 本番サーバー上ではComposerやNode.jsによる依存関係の取得・ビルドを行わない。
- GitHub Actionsでテストとビルドを実行し、成功した成果物だけをSSH・rsyncで同期する。
- APIと各FRONTは同一リポジトリから作成するが、成果物と配備先を分ける。
- 本番接続情報と配置パスはGitHubの`production` Environmentで管理する。

詳細なソースと成果物の境界は[ディレクトリ構成](102_directory-structure.md)、本番ドメインは[サーバ・インフラ構成](103_infrastructure.md)を参照する。

## 現在のワークフロー

2026年8月25日時点のワークフローは、PHPの検証、`app/`の既存Viteビルド、`frontend/`の`operator`・`admin`ビルドを実行する。成果物にはCakePHP、`app/webroot/build/`および`frontend/dist/{app}/`を含め、単一の配置先へ同期する。

- `frontend/`の依存管理とアプリ別ビルドは実装済みである。
- `operator`と`admin`の成果物は、現在のリリース内の`frontend/operator/`、`frontend/admin/`へ格納する。
- APIと各FRONTをサブドメイン別の配置先へ同期する処理は未実装である。
- GitHub Environmentの表示URLとヘルスチェックURLには旧ドメイン設定が残っているため、`squash-platform.com`への切替が必要である。

そのため、サブドメイン別の本番配備を有効にする前に、ワークフロー、GitHub Environmentおよびサーバー側配置先を同時に更新する。

## デプロイの検証順序

1. APIの依存関係を取得する。
2. PHPテスト、コーディング規約および静的解析を実行する。
3. FRONTの依存関係を取得する。
4. 対象となる各FRONTアプリをビルドする。
5. API用成果物とFRONT別成果物を作成する。
6. 成果物の必須ファイルと配置先を検証する。
7. APIとFRONTを対応する配置先へ同期する。
8. 必要な場合だけデータベースマイグレーションを実行する。
9. APIおよび主要FRONTのヘルスチェックを行う。

途中の検証に失敗した場合は後続の本番配備へ進まない。

## GitHub Environment

### Secrets

| 名前 | 内容 |
|---|---|
| `ONAMAE_SSH_HOST` | RSプランのSSH接続先ホスト |
| `ONAMAE_SSH_USER` | デプロイ専用SSHユーザー |
| `ONAMAE_SSH_PRIVATE_KEY` | 登録済み公開鍵に対応する秘密鍵 |
| `ONAMAE_SSH_KNOWN_HOSTS` | 接続先ホスト鍵 |

秘密鍵、実際のホスト、ユーザー名および接続情報を文書やGitへ記載しない。

### Variables

現行の`ONAMAE_SSH_PORT`、`ONAMAE_DEPLOY_PATH`、`ONAMAE_HEALTHCHECK_URL`、`ONAMAE_RUN_MIGRATIONS`は単一配置向けである。分離配備時のAPIおよびFRONT別の変数名と配置先は検討中とし、ワークフロー更新時に確定する。

## サーバー側の準備

- `api.squash-platform.com`と各FRONTドメインのドキュメントルートを分ける。
- API配置先へGit管理外の環境別設定を作成する。
- APIの`logs`と`tmp`をWebサーバーから書き込み可能にする。
- アップロードファイルをソース同期の削除対象から除外する。
- 配置先ごとに誤同期を防ぐ目印ファイルを置き、絶対パスと目印の両方を検証する。
- TLS証明書とDNSが対象ドメインに対して有効であることを確認する。

実際の配置パス、認証情報およびデータベース接続情報は本書へ記載しない。

## 同期時の保護

APIの同期では少なくとも次を削除対象から除外する。

- 配置先の目印ファイル
- `config/app_local.php`と`config/.env`
- `logs`と`tmp`
- `webroot/uploads`

FRONTの同期では対象アプリの成果物だけを対応するドキュメントルートへ同期し、別アプリやAPIの配置先へ`--delete`を適用しない。

## データベース変更

本番マイグレーションの自動実行は初期状態で無効とする。バックアップ、失敗時の復旧手順、後方互換性および実行順を確認した後に有効化する。

マイグレーションを実行する場合、API同期後にCakePHP Migrationsで未適用分だけを順番に適用する。失敗時は後続のヘルスチェックへ進まない。

## RSプランの制約

RSプランではSSH、scp、rsync、tar、PHPを利用できる。国外IPアドレスからのSSH制限によりGitHub-hosted runnerから接続できない場合は、制限方針を確認するか、国内ネットワーク上の専用self-hosted runnerを検討する。

## 検討中

- APIおよびFRONT別の実配置パスとGitHub Environment Variables
- GitHub-hosted runnerとself-hosted runnerの選択
- 本番マイグレーションを有効化する時期
- 自動バックアップ、ロールバック、メンテナンス表示
- FRONT別のヘルスチェックと部分的な配備失敗時の扱い
