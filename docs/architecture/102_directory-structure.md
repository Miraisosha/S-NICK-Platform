# ディレクトリ構成

## 位置づけ

本書は、APIとFRONTの責務、ソース配置、依存関係およびビルド境界を定義する。詳細な画面構成は[フロントエンド](301_frontend.md)、CakePHP内部の責務は[バックエンド](302_backend.md)、URL設計は[API](303_api.md)を参照する。

## 状態

- **決定済み・実装済み**：APIとFRONTは同一リポジトリで管理し、APIを`app/`、FRONTを`frontend/`へ分けて依存関係を管理する。
- **決定済み・一部実装済み**：FRONTは利用者区分ごとに独立したViteエントリーを持つ。2026年8月25日時点では`operator`と`admin`を実装済みである。
- **移行中**：`app/resources/js/front/`とCakePHPのHTML画面は互換性のため残っている。廃止時期は検討中である。

## 現在のディレクトリ

```text
（リポジトリ直下）
├─ app/                         # API（CakePHP）
│  ├─ composer.json
│  ├─ composer.lock
│  ├─ config/
│  │  └─ routes.php
│  ├─ src/
│  │  ├─ Controller/
│  │  │  └─ Api/V1/            # JSON API Controller
│  │  ├─ Model/                 # CakePHP Entity・Table
│  │  └─ Service/               # 認証・イベント等のApplication Service
│  ├─ resources/js/front/       # 移行期間中の既存Viteエントリー
│  ├─ templates/                # 移行期間中のHTML画面、診断・エラー画面
│  └─ tests/TestCase/
│     └─ Controller/Api/V1/
├─ frontend/                    # FRONT（Vue.js / Vite）
│  ├─ package.json
│  ├─ package-lock.json
│  ├─ vite.config.js
│  ├─ entries/
│  │  ├─ operator/index.html
│  │  └─ admin/index.html
│  └─ src/
│     ├─ apps/
│     │  ├─ operator/           # 運営者アプリ
│     │  └─ admin/              # 管理者アプリ
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

| アプリコード | 用途 | 本番ドメイン | 導入時期 | 実装状態 |
|---|---|---|---|---|
| `home` | 公開ランディング | `squash-platform.com` | フェーズ1 | 未実装 |
| `operator` | 運営者・スタッフ | `operator.squash-platform.com` | フェーズ1 | 実装中 |
| `entry` | Webエントリー | `entry.squash-platform.com` | フェーズ3 | 未実装 |
| `player` | 選手マイページ | `player.squash-platform.com` | フェーズ3 | 未実装 |
| `marker` | マーカー | `marker.squash-platform.com` | フェーズ1 | 未実装 |
| `live` | 配信・OBS | `live.squash-platform.com` | フェーズ2以降 | 未実装 |
| `display` | 観客・大型表示 | `display.squash-platform.com` | フェーズ1 | 未実装 |
| `admin` | プラットフォーム管理 | `admin.squash-platform.com` | フェーズ1 | 実装中 |

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
- ビルド成果物は`frontend/dist/{app}/`へアプリ別に出力する。

## 残る移行事項

- `app/resources/js/front/`とCakePHP HTML画面の維持・廃止範囲
- 未実装FRONTの追加順序とアプリコード
- GitHub Actions成果物のAPI・FRONT別配備先
- 各FRONTの本番ドキュメントルートとSPAフォールバック設定

既存画面を廃止する場合は、利用中の機能とデプロイへの影響を確認してから行う。
