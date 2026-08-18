# GitHub Actionsによる本番デプロイ

## 位置づけ

`main`ブランチへのマージ後、GitHub Actionsでテストとビルドを実行し、成功した成果物をお名前.com レンタルサーバー RSプランへSSH・rsyncで同期する。

ワークフローは [`.github/workflows/deploy-production.yml`](../.github/workflows/deploy-production.yml) で管理する。GitHub上で必要なSecretsとVariablesを設定するまでは本番接続できない。

## 決定済み

- `main`へのpushを本番デプロイのトリガーとする。通常運用ではPull Requestのマージにより発生させる。
- 手動再実行用に`workflow_dispatch`も提供する。
- PHPテスト、コーディング規約、フロントエンドビルドが成功した場合だけデプロイする。
- 本番サーバー上でComposerやNode.jsによる依存関係の取得・ビルドを行わず、GitHub Actions側で`vendor`とVite成果物を作成する。
- GitHubの`production` EnvironmentにSSH認証情報を保存し、同時に複数の本番デプロイを実行しない。
- 検証・ビルドと本番配備を別ジョブに分け、SSH Secretsは本番配備ジョブだけへ渡す。両ジョブ間は7日で削除されるGitHub Actions Artifactで成果物を引き渡す。
- `config/app_local.php`、`config/.env`、`logs`、`tmp`、`webroot/uploads`は同期・削除対象から除外する。
- 本番配置先に`.snick-platform-deploy-target`が存在しない場合は、誤削除防止のためデプロイを中止する。

`push`イベントはPull Requestのマージだけでなく`main`への直接pushでも発生するため、GitHubのブランチ保護で`main`への直接pushを禁止する。

## GitHub Environment設定

GitHubリポジトリのSettings → Environmentsで`production`を作成し、デプロイブランチを`main`だけに制限する。必要に応じてRequired reviewersを設定する。

### Secrets

| 名前 | 内容 |
|---|---|
| `ONAMAE_SSH_HOST` | RSプランのSSH接続先ホスト |
| `ONAMAE_SSH_USER` | デプロイ専用のSSHユーザー |
| `ONAMAE_SSH_PRIVATE_KEY` | 登録済み公開鍵に対応する秘密鍵 |
| `ONAMAE_SSH_KNOWN_HOSTS` | 接続先ホスト鍵をknown_hosts形式で記録した値 |

秘密鍵はデプロイ専用とし、パスフレーズなしでGitHub Environment Secretに保存する。秘密鍵、実際の接続先、ユーザー名をリポジトリへ記載しない。

### Variables

| 名前 | 内容 | 例・状態 |
|---|---|---|
| `ONAMAE_SSH_PORT` | SSH接続ポート | 実際の契約情報から設定 |
| `ONAMAE_DEPLOY_PATH` | CakePHPアプリケーションの絶対配置先 | 実際のサーバーパス確認後に設定 |
| `ONAMAE_HEALTHCHECK_URL` | デプロイ後にHTTP 200を確認するURL | `https://platform.s-nick.com/` |
| `ONAMAE_RUN_MIGRATIONS` | `true`の場合だけマイグレーションを実行 | 初期値は`false` |

## サーバー側の初回準備

次は実際の配置先を確認したうえで、SSH接続して一度だけ実施する。

1. CakePHPアプリケーションの配置先を作成する。
2. 配置先の`webroot`を`platform.s-nick.com`のドキュメントルートに設定する。
3. 配置先の`config/app_local.php`へ本番データベース等の環境別設定を作成する。
4. 配置先直下に空の`.snick-platform-deploy-target`を作成する。
5. `logs`と`tmp`をWebサーバーから書き込み可能にする。

実際のSSHホスト、ユーザー名、配置パス、秘密鍵、データベースパスワードは文書やGitへ記載しない。

## 同期動作

rsyncは本番配置先をGitHub Actionsの成果物へ同期し、成果物に存在しない旧ソースを`--delete-delay`で削除する。ただし、次は保護する。

- `.snick-platform-deploy-target`
- `config/app_local.php`と`config/.env`
- `logs`と`tmp`
- `webroot/uploads`

配置パスは絶対パスに限定し、`/`、空白を含むパス、目印ファイルがないパスへの同期を拒否する。

## データベース変更

`ONAMAE_RUN_MIGRATIONS`は初期状態で`false`とする。本番バックアップ、失敗時の復旧手順、マイグレーションの後方互換性を確認した後に`true`へ変更する。

`true`の場合、Runnerはアプリケーションの同期後にSSH経由で`bin/cake.php migrations migrate`を実行する。未適用のマイグレーションだけが順番に適用され、失敗した場合は後続のヘルスチェックへ進まずデプロイを失敗として終了する。SQLファイルを直接順番に実行する方式は使用しない。

## RSプランのSSH制約

RSプランではSSH、scp、rsync、tar、PHPが利用できる。国外IPアドレスからのSSH接続制限が有効な場合、GitHub-hosted runnerから接続できない可能性がある。その場合は次のいずれかを選択する。

- 制限方針を確認したうえでGitHub-hosted runnerからの接続を許可する。
- 国内ネットワーク上にデプロイ専用のself-hosted runnerを設置し、`runs-on`を専用ラベルへ変更する。

self-hosted runnerを採用する場合は、他用途と共用せず、アクセス権と更新・監視方法を別途設計する。

## 検討中

- 初回の実配置パスとドキュメントルート
- GitHub-hosted runnerとself-hosted runnerのどちらを使用するか
- 本番マイグレーションを有効化する時期
- 自動バックアップ、ロールバック、メンテナンス表示の方式
