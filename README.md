# S-NICK Platform

S-NICK Platform は、スカッシュ大会の企画、エントリー受付、試合運営、スコア管理、ライブ配信、結果公開までを一元的に支援する統合プラットフォームです。

現在は仕様検討段階です。フェーズ1では、運営者による選手の手動登録、イベント・ドロー・スケジュール、マーカー、リアルタイム得点表示、結果公開までの大会運営中核機能を目指します。エントリー受付の自動化等はフェーズ2、選手自身のWeb申込、決済、LINE、YouTube配信設定、ランキング公開はフェーズ3とします。

## 目指すサービス

- ユーザー登録、ログイン、アカウント復旧
- イベント、カテゴリ、エントリー、選手の管理
- ドロー作成、試合進行、コート管理
- マーカーによるスコア入力
- 観客向けの大型モニター・タブレット表示
- OBSとYouTube Liveを利用したライブ配信
- 大会ロゴ・スポンサー表示
- スタッフ、ランキング、結果公開

## 技術構成

| 区分 | 採用技術・サービス |
|---|---|
| 本番環境 | お名前.com レンタルサーバー RSプラン |
| バックエンド | PHP 8.5 / CakePHP |
| フロントエンド | Vue.js / Bootstrap |
| データベース | MySQL |
| ソース管理 | GitHub |
| サーバー接続 | SSH（接続確認済み） |

CakePHP、Vue.js、Bootstrap、MySQLの詳細バージョンと開発・デプロイ方法は検討中です。

## ドメイン構成

| URL | 役割 |
|---|---|
| `platform.s-nick.com` | 大会運営システム |
| `entry.s-nick.com` | エントリー受付 |
| `live.s-nick.com` | ライブ配信・観戦 |
| `ranking.s-nick.com` | ランキング |
| `insurance.s-nick.com` | 保険申込 |
| `api.s-nick.com` | API |

## ドキュメント

- [Requirements v1.0（機能・画面・イベント・業務ルール）](docs/specifications/README.md)
- [プロジェクト概要](docs/01_project-overview.md)
- [システム構成](docs/02_system-architecture.md)
- [サービス・業務領域計画](docs/03_service-domain-plan.md)
- [ライブ配信](docs/04_live-streaming.md)
- [ネットワーク・機材](docs/05_network-and-hardware.md)
- [開発ロードマップ](docs/06_development-roadmap.md)

`docs/01`～`06`はプロジェクト全体の方針・基本設計、`docs/specifications/`は利用者別の詳細要件と実装へ引き継ぐ仕様を管理します。

## 現在の方針

- 決定済み事項と検討中事項を区別して記録します。
- 現段階では実装コードを作成しません。
- 仕様確定後、小さな機能単位で設計・実装・テストを進めます。
