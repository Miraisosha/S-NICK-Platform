# Squash Platform

**日本語表記：スカッシュ　プラットフォーム**

Squash Platformは、スカッシュ大会の企画、エントリー受付、試合運営、スコア管理、ライブ配信、結果公開までを一元的に支援する統合プラットフォームです。

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
| バックエンド | PHP 8.5 / CakePHP 5.4 |
| フロントエンド | Vue.js 3.5 / Bootstrap 5.3 / Vite 8 |
| データベース | MySQL |
| ソース管理 | GitHub |
| サーバー接続 | SSH（接続確認済み） |

ローカル開発ではNode.js 24、MySQL 8.4 LTSを使用します。本番MySQLのバージョンは検討中です。本番デプロイはGitHub Actionsからお名前.com RSプランへSSH・rsyncで行います。

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
- [画面遷移図](docs/specifications/ScreenFlow.md)
- [概念ER設計](docs/specifications/DataModel.md)
- [概念クラス設計](docs/specifications/ClassDesign.md)
- [プロジェクト概要](docs/01_project-overview.md)
- [システム構成](docs/02_system-architecture.md)
- [サービス・業務領域計画](docs/03_service-domain-plan.md)
- [ライブ配信](docs/04_live-streaming.md)
- [ネットワーク・機材](docs/05_network-and-hardware.md)
- [開発ロードマップ](docs/06_development-roadmap.md)
- [ローカル開発環境](docs/07_local-development.md)
- [GitHub Actionsによる本番デプロイ](docs/08_deployment.md)

`docs/01`～`06`はプロジェクト全体の方針・基本設計、`docs/specifications/`は利用者別の詳細要件と実装へ引き継ぐ仕様を管理します。

## 現在の方針

- 決定済み事項と検討中事項を区別して記録します。
- 要件整理を継続しながら、合意済みの範囲を小さな機能単位で設計・実装・テストします。
- 未決定事項は推測で実装せず、仕様を確認してから進めます。
