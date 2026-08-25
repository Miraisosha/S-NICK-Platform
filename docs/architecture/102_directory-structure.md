# ディレクトリ構成

## 位置づけ

本書は、APIとFRONTの責務、ソース配置、依存関係およびビルド境界を定義する。詳細な画面構成は[フロントエンド](301_frontend.md)、CakePHP内部の責務は[バックエンド](302_backend.md)、URL設計は[API](303_api.md)を参照する。

## 状態

- **決定済み**：APIとFRONTは同一リポジトリで管理し、ソースと依存関係を分離する。
- **決定済み**：FRONTは利用者区分ごとに独立したViteエントリーを持つ。
- **目標構成**：APIを`app/`、FRONTをリポジトリ直下の`frontend/`へ配置する。
- **現在**：2026年8月25日時点では`frontend/`への移行前で、既存Vueソースは`app/resources/js/front/`にある。`operator`と`admin`の独立FRONTが実装済みという扱いにはしない。

## 目標ディレクトリ

```text
（リポジトリ直下）
├─ app/                         # API（CakePHP）
│  ├─ config/
│  │  └─ routes.php
│  ├─ src/
│  │  ├─ Controller/
│  │  │  └─ Api/V1/            # JSON API Controller
│  │  ├─ Model/                 # CakePHP Entity・Table
│  │  ├─ Service/               # Application Service等の候補
│  │  └─ Policy/                # 認可・業務ルールの候補
│  ├─ templates/                # 診断・エラー等、API運用に必要な最小範囲
│  └─ tests/TestCase/
│     └─ Controller/Api/V1/
├─ frontend/                    # FRONT（Vue.js / Vite）
│  ├─ package.json
│  ├─ vite.config.js
│  ├─ entries/
│  │  └─ {app}/index.html       # アプリ別HTMLエントリー
│  └─ src/
│     ├─ apps/{app}/
│     │  ├─ main.js
│     │  ├─ router/routes.js
│     │  ├─ views/
│     │  └─ components/
│     ├─ router/                # 全アプリ共通のルーター初期化・ガード
│     ├─ components/common/     # 共有UI部品
│     ├─ api/                   # 共有APIクライアント
│     ├─ stores/                # 共有Piniaストア
│     ├─ composables/
│     ├─ utils/
│     └─ assets/
├─ docker/                      # ローカル開発用コンテナ
├─ docs/
└─ compose.yaml
```

## FRONTアプリ

| アプリコード | 用途 | 本番ドメイン | 導入時期 |
|---|---|---|---|
| `home` | 公開ランディング | `squash-platform.com` | フェーズ1 |
| `operator` | 運営者・スタッフ | `operator.squash-platform.com` | フェーズ1 |
| `entry` | Webエントリー | `entry.squash-platform.com` | フェーズ3 |
| `player` | 選手マイページ | `player.squash-platform.com` | フェーズ3 |
| `marker` | マーカー | `marker.squash-platform.com` | フェーズ1 |
| `live` | 配信・OBS | `live.squash-platform.com` | フェーズ2以降 |
| `display` | 観客・大型表示 | `display.squash-platform.com` | フェーズ1 |
| `admin` | プラットフォーム管理 | `admin.squash-platform.com` | フェーズ1 |

フェーズ前のアプリを空の雛形として先行実装するかは検討中とし、必要になるまでディレクトリの存在を必須にしない。

## 配置規約

### API

- CakePHPは`/api/v1/...`のJSON APIを提供する。
- API Controllerは`App\Controller\Api\V1`名前空間と`app/src/Controller/Api/V1/`へ配置する。
- HTTPメソッドを含むルートを`app/config/routes.php`へ明示する。
- API Controllerのテストは`app/tests/TestCase/Controller/Api/V1/`へ対応付ける。

### FRONT

- 各アプリは固有の`main.js`、ルート、画面、アプリ固有コンポーネントを持つ。
- 複数アプリで利用する処理だけを共通ディレクトリへ置き、アプリ固有の業務処理を安易に共有しない。
- アプリ内部はVue RouterによるSPAとし、アプリ間は通常のリンク遷移とする。
- ビルド成果物は`frontend/dist/{app}/`へアプリ別に出力する目標とする。

## 移行時の確認事項

- 現在の`app/resources/js/front/`から`frontend/`へ移す対象と廃止する対象
- Composer側とnpm側のビルド依存関係
- Docker Composeの`node`サービスのマウント先
- GitHub Actionsのキャッシュ、ビルドコマンド、成果物およびアプリ別配備先
- ローカル開発時のFRONTポートとAPIのCORS許可オリジン

移行は実装作業として別途行い、この文書整理だけを理由に既存ソースを移動しない。
