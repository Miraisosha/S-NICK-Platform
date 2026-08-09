# Squash Platform 画面遷移

## 位置づけ

本書は、利用者別仕様に定義された画面の入口と主要な遷移を横断的に確認するための資料である。画面内の操作、試合状態および業務ルールの詳細は各仕様書を正本とする。

- 確認時点：2026年8月7日
- 実線：決定済みの主要遷移
- 破線：後続フェーズ、または詳細が検討中の遷移
- `[F1]`、`[F2]`、`[F3]`：提供フェーズ
- 画面IDのない確認ダイアログは、遷移元画面の一部として扱う

## 1. 全体入口

```mermaid
flowchart LR
    Start["Squash Platformへアクセス"] --> Public["SCR-PUB-111<br/>公開トップ"]

    Public --> Event["SCR-PUB-121<br/>イベント詳細"]
    Event --> Category["SCR-PUB-131<br/>カテゴリ一覧"]
    Event --> Progress["SCR-PUB-141<br/>公開進行画面"]
    Event --> Result["SCR-PUB-161<br/>結果・アーカイブ"]
    Event -.-> Live["SCR-PUB-151<br/>ライブ一覧・視聴 [F3]"]

    Start --> Login["SCR-OPR-214<br/>ログイン"]
    Login --> Menu["SCR-OPR-231<br/>ログイン後トップ／メニュー"]
    Menu --> Quick["クイック起動"]
    Quick --> Marker["SCR-MKR-321<br/>マーカー画面"]
    Menu --> Events["SCR-OPR-2401<br/>イベント一覧"]

    Start --> AdminLogin["管理者専用ログイン"]
    AdminLogin --> Admin["SCR-ADM-5001<br/>管理者ダッシュボード"]

    Event -.-> Entry["SCR-PLY-421<br/>Webエントリー [F3]"]
```

公開画面、通常ユーザー画面およびプラットフォーム管理者画面は、認証経路を分離する。クイック起動だけはフェーズ1のログイン後トップへ必ず表示し、ダッシュボードのその他の構成はフェーズ2で拡張する。

## 2. 運営者による大会準備・当日運営

```mermaid
flowchart TD
    Menu["SCR-OPR-231<br/>運営者メニュー"] --> List["SCR-OPR-2401<br/>イベント一覧"]
    List --> New["SCR-OPR-2403<br/>イベント新規登録"]
    New --> Detail["SCR-OPR-2402<br/>イベント詳細"]
    List --> Detail
    Detail --> Edit["SCR-OPR-2404<br/>イベント編集"]
    Edit --> Detail

    Detail --> Category["SCR-OPR-2405<br/>カテゴリ管理"]
    Detail --> Player["SCR-OPR-2406<br/>選手管理"]
    Detail --> Staff["SCR-OPR-252<br/>スタッフ管理"]
    Detail --> Court["SCR-OPR-261<br/>施設・コート選択"]
    Detail --> Sponsor["SCR-OPR-251<br/>スポンサー管理"]
    Detail --> QR["SCR-OPR-274<br/>コート別固定QRコード<br/>表示・印刷"]
    QR --> MarkerQR["コート別マーカー用QR"]
    QR --> DisplayQR["コート別大型得点ボード用QR"]
    MarkerQR --> QROutput["コピー／保存／印刷"]
    DisplayQR --> QROutput

    Category --> Draw["SCR-OPR-2407<br/>ドロー作成"]
    Player --> Draw
    Court --> Schedule["SCR-OPR-2408<br/>スケジュール作成・表示"]
    Draw --> Schedule
    Schedule --> Match["SCR-OPR-2409<br/>試合管理"]

    Match --> Monitor["SCR-OPR-2410<br/>試合状況モニター"]
    Match --> MarkerAssign["SCR-OPR-271<br/>マーカー担当・端末管理"]
    Match --> DisplaySetup["SCR-OPR-272<br/>大型モニター表示設定"]
    DisplaySetup --> Scoreboard["SCR-OPR-273<br/>コート別大型得点ボード"]
    Match -.-> Stream["SCR-OPR-281<br/>配信一覧・設定 [F3]"]
```

フェーズ1でもドローとスケジュールは下書きへ保存し、運営者が確認してから公開する。フェーズ2のスケジュール自動生成では、`SCR-OPR-2408`内で複数候補を比較し、選択した1案を下書きへ複製して手動調整・公開する。候補および下書きは、公開するまでマーカー画面や公開画面へ反映しない。

イベントに設定した施設・コートは大会準備時点で確定しているため、イベント詳細の直下から`SCR-OPR-274`を開く。各コートのマーカー用QRコードと大型得点ボード用QRコードは固定とし、マーカー割当画面や試合管理画面を経由しない。

## 3. マーカー起動

### 3.1 通常モード

```mermaid
flowchart TD
    Start["QRコード／接続コード<br/>ホーム画面ショートカット"] --> Auth{"ログイン・権限・<br/>イベント・コートを確認"}
    Auth -->|"無効・期限切れ・権限なし"| Error["起動不可案内"]
    Auth -->|"有効"| Resume{"再開対象状態の<br/>試合があるか"}

    Resume -->|"なし"| List["SCR-MKR-311<br/>コート別当日試合一覧"]
    Resume -->|"1件"| Owner{"この端末が<br/>操作権を保持しているか"}
    Owner -->|"はい"| Marker["SCR-MKR-321<br/>マーカー画面／状態復元"]
    Owner -->|"いいえ"| ReadOnly["最新得点を閲覧<br/>端末個体番号を表示"]
    ReadOnly --> Takeover{"オンラインで<br/>引き継ぐか"}
    Takeover -->|"通常／警告付き強制"| Marker
    Takeover -->|"引き継がない"| ReadOnly
    Resume -->|"複数"| Conflict["競合警告・運営者確認"]

    List --> Select["開始前試合を選択"]
    Select --> Online{"オンラインか"}
    Online -->|"いいえ"| Block["新規試合開始不可"]
    Online -->|"はい"| Confirm["試合開始確認"]
    Confirm -->|"キャンセル"| List
    Confirm -->|"開始・操作ロック成功"| Warmup["SCR-MKR-325<br/>ウォームアップ"]
    Warmup --> Marker

    Error --> Start
    Block --> List
    Conflict --> Marker
```

再開対象として使用する正式な試合状態は、`warmup`、`preparing`、`in_progress`、`interval`、`injury_time`、`suspended`である。独自の包括状態コードは使用しない。

### 3.2 クイックモード

```mermaid
flowchart LR
    Login["ログイン後トップ／メニュー"] --> Button["クイック起動"]
    Button --> Create["空のクイック試合を作成<br/>初期値：1ゲーム・11点・2点差"]
    Create --> Marker["SCR-MKR-321<br/>マーカー画面"]
    Marker --> Settings["SCR-MKR-322<br/>クイック設定・選手表示設定"]
    Settings --> Marker
```

クイックモードは事前入力画面と`SCR-MKR-311`を経由しない。イベント名、カテゴリ名、ラウンド名、コート番号および選手名は空欄のまま開始し、後から設定できる。

## 4. マーカー画面内の遷移

```mermaid
flowchart TD
    Marker["SCR-MKR-321<br/>マーカー画面"]

    Marker --> Settings["SCR-MKR-322<br/>クイック設定・選手表示設定"]
    Settings --> Marker

    Marker --> GameDetail["SCR-MKR-323<br/>ゲーム詳細・読取専用"]
    GameDetail --> Marker

    Marker --> Judgment["SCR-MKR-324<br/>Conduct・Injury・Forfeit"]
    Judgment --> Marker

    Marker --> Timer["SCR-MKR-325<br/>タイマー"]
    Timer --> Marker

    Marker -->|"ゲーム終了・試合継続"| Interval["SCR-MKR-325<br/>ゲーム間インターバル"]
    Interval -->|"次ゲーム開始"| Marker

    Marker -->|"試合終了条件／終了操作"| Finish["SCR-MKR-326<br/>試合終了確認"]
    Finish -->|"キャンセル"| Marker
    Finish -->|"確定"| Finished["試合結果保存・finished"]
    Finished --> Next{"終了後の表示先"}
    Next -.-> List["SCR-MKR-311<br/>コート別当日試合一覧"]

    Finished -->|"誤終了"| Undo["終了操作をUNDO"]
    Undo --> Marker
    Finished -->|"全履歴をリセット"| Reentry["訂正用下書きで最初から再入力"]
    Reentry --> Marker
```

試合終了確定後に自動的に一覧へ戻すか、結果を表示したまま「一覧へ戻る」を押させるかは検討中とする。誤終了後のUNDOと全履歴リセット・再入力は、担当マーカーまたはイベント運営者が実行できる。

## 5. ブラウザ終了・オフラインからの復帰

```mermaid
flowchart TD
    Close["試合中にブラウザ終了／通信切断"] --> Local["端末へ操作履歴・状態・<br/>タイマー終了予定日時を保存"]
    Local --> Reopen["同じ端末・ブラウザで再起動"]
    Reopen --> Restore["SCR-MKR-321へ復帰"]

    Restore --> Timer{"作動中タイマーがあったか"}
    Timer -->|"終了前"| Remaining["経過時間から残り時間を復元"]
    Timer -->|"終了後"| Expired["0・時間終了を表示"]
    Expired --> Confirm["次の操作をマーカーが確認"]
    Confirm --> Marker["次タイマー／次ゲーム／試合再開"]
    Timer -->|"なし"| Marker

    Restore --> Network{"オンラインへ復旧したか"}
    Network -->|"はい"| Sync["未同期操作を順序どおり送信"]
    Network -->|"いいえ"| Offline["開始済み試合を継続"]
    Offline --> Network
    Sync --> Conflict{"別端末の分岐があるか"}
    Conflict -->|"なし"| Marker
    Conflict -->|"あり"| Resolve["運営者が端末別記録を1つ選択"]
    Resolve --> Marker
```

ブラウザ不在中は、閉じた時点で動作していた1つのタイマーが0になるまでだけ経過させる。次のタイマー、ゲームまたは試合へ自動遷移しない。

## 6. 大型表示・OBSの入口

```mermaid
flowchart LR
    Event["SCR-OPR-2402<br/>イベント詳細"] --> QR["SCR-OPR-274<br/>コート別固定QRコード"]
    QR --> DisplayQR["大型得点ボード用QR<br/>イベント・コート固定"]
    DisplayQR --> Display["SCR-OPR-273<br/>コート別大型得点ボード"]

    Operator["SCR-OPR-272<br/>大型モニター表示設定"] --> Display

    Marker["SCR-MKR-321<br/>確定得点・判定"] --> Display
    Marker -.-> OBS["OBS Browser Source [F2]"]
    Operator -.-> Layout["イベント別OBSレイアウト設定 [F2]"]
    Layout -.-> OBS
```

大型得点ボード用QRコードは、マーカー用とは別の固定コードとしてイベント・コートごとに作成し、同じコートの1～2台の表示端末でイベント開催終了まで再利用する。

## 7. 管理者画面

```mermaid
flowchart TD
    Login["管理者専用ログイン"] --> Dashboard["SCR-ADM-5001<br/>管理者ダッシュボード"]
    Dashboard --> Accounts["SCR-ADM-5101<br/>アカウント一覧"]
    Accounts --> Account["SCR-ADM-5102<br/>アカウント詳細"]
    Account --> Suspend["SCR-ADM-511<br/>利用停止・再開"]
    Suspend --> Account
    Dashboard --> Court["SCR-ADM-522<br/>施設・コート管理"]
    Dashboard --> Ranking["SCR-ADM-521<br/>協会ランキング取込"]
```

## 8. 画面遷移として残る検討事項

1. 試合終了確定後に`SCR-MKR-311`へ自動遷移するか、結果確認画面に留まるか。
2. 得点入力後にクイック設定で最大ゲーム数、ゲーム終了点または必要点差を変更した場合の確認画面と適用方法。
3. ドロー・スケジュールの公開取消を許可する条件と、取消後に表示する公開版。
4. フェーズ3のWebエントリー、決済、キャンセル待ちおよび選手マイページの詳細遷移。
5. フェーズ3の配信設定、YouTube連携および配信終了後のアーカイブ遷移。

## 関連資料

- [機能・フェーズ一覧](FunctionalIndex.md)
- [運営者仕様](200_Operator.md)
- [マーカー仕様](300_Marker.md)
- [パブリック仕様](100_Public.md)
- [選手仕様](400_Player.md)
- [管理者仕様](500_Admin.md)
- [試合状態遷移](yaml/state-machine.yaml)
