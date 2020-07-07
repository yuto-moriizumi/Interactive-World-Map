<?php
error_reporting(0);
mb_language("ja");
mb_internal_encoding('UTF-8');
 
//JSから受信
$url=$_POST["data"];
$command = explode(",",$_POST["data"],2); //命令取り出し

if( strcmp($command[0], "MapClick") == 0 ){ //マップクリック処理
    $MousePoint = explode(",",$command[1]);
    $image = imagecreatefrompng("map3.png");
    $rgb = imagecolorat($image, $MousePoint[1], $MousePoint[2]); //0には現在の国名が入ってます
    $colors = imagecolorsforindex($image, $rgb);
    
    //州ファイルから読み込み
    $filename = "states/".$colors["red"].$colors["green"].$colors["blue"].".txt";
    $fp = fopen($filename, 'r');
    $state = fgets($fp);
    $country = fgets($fp);
    fclose($fp);

    //現在のターンを取得
    $fp = fopen("turn.txt", 'r');
    $turn = fgets($fp);
    fclose($fp);

    //対象国ファイルから読み込み
    $fp = fopen("countries/".$country.".txt", 'r');
    $IsCountryAffectedNA=0;
    if ($fp !== false){
        $i=0;
        $Loadedline = fgets($fp);        
        while($Loadedline !== false){ //ファイル終端でなければ実行
            $part = explode(",", $Loadedline);
            if($part[0]==$MousePoint[0]) $AvailableDemands = $part[1];//読み込んだ国名と自国名が同じであれば要求可能州数を保存
            $NonAggressionDurationTemp = explode(".",$part[2]);
            if ($NonAggressionDurationTemp[0] >= $turn){ //現在のターン以上の年までの不可侵条約であれば情報を保存
                $NonAggressionCountry[$i] = $part[0];
                $NonAggressionDuration[$i] = $NonAggressionDurationTemp[0];
                if ($part[0]==$MousePoint[0]) $IsCountryAffectedNA=1;
            }
            $i=$i+1;
            $Loadedline = fgets($fp);
        }
        fclose($fp);
        if($MousePoint[0]=="NotCountry") $AvailableDemands ="操作国選択必須";
    }else{
        $AvailableDemands="国データを保存したファイルがありません";
        $i=0;
    }

    
    //出力をまとめる 形式：選択州名,選択国名,選択国名に対する有効な請求州数,選択国の不可侵情報
    $result = $state.",".$country.",".$AvailableDemands.",".$IsCountryAffectedNA.",";
    for($i--; $i>=0; $i--){
        if ( empty($NonAggressionCountry[$i]) === false) { //空の不可侵情報を削除
            $result = $result.$NonAggressionCountry[$i]."と".$NonAggressionDuration[$i]."年まで ";
        }
    }
    print $result;

}elseif(strcmp($command[0], "DeclareWar") == 0){ //宣戦布告処理
    $country=explode(",",$command[1]); //操作国と対象国に分割
    
    //現在のターンを取得し不可侵終了ターン（現在は1年）を計算
    $fp = fopen("turn.txt", 'c+');
    $turn = fgets($fp);
    fclose($fp);
    $turn = $turn + 1;
    
    //仮の勝敗判定　51以上で勝利
    $RandomNum = rand ( 1 , 100 ) ;
    if ($RandomNum>51) $IsControllCountryWon = 1;
    else $IsControllCountryWon = 0;
     //対象国に勝利したら、対象国.txtに自国名を追記 ！！！！！両方に書くように変更する必要あり
        
        
        //対象国側のファイルに操作国の外交データを書き込む
        $filename = "countries/".$country[1].".txt";
        $fp = fopen($filename, 'r+');
        $i=0;
        $Loadedline = fgets($fp);
        $IsControllCountryDataExist = 0;
        while($Loadedline !== false){ //ファイル終端でなければ実行
            $part = explode(",", $Loadedline);
            if($part[0]==$country[0]) { //自国データが見つかれば
                $IsControllCountryDataExist = 1;
                if ($IsControllCountryWon==1) $part[1]=$part[1]+1; //操作国が勝利した場合請求できる州を 1 つ追加 操作国が敗北した場合はそのまま
                //$file = file($filename);
                //unset($file[$i]); //その行全体を削除
                //file_put_contents($filename, $file);
                fwrite ($fp, $country[0].$part[1].$turn.".\n");
                fseek($fp,0,SEEK_END); //ファイルポイントを末尾に持ってきてループを離脱
            }
            $i=$i+1;
            $Loadedline = fgets($fp);
        }
        if($IsControllCountryDataExist=0){//自国データが存在しなければ
            if ($IsControllCountryWon==1){
                fwrite ($fp, $country[0].1.$turn.".\n"); //請求できる州を 1 つ追加した外交データを追記
            }else{
                fwrite ($fp, $country[0].0.$turn.".\n"); //外交データを追記
            }            
        }
        fclose($fp);
        
        //操作国側のファイルに対象国の外交データを書き込む
        $filename = "countries/".$country[0].".txt";
        $fp = fopen($filename, 'c+');
        $i=0;
        $Loadedline = fgets($fp);
        $IsControllCountryDataExist = 0;
        while($Loadedline !== false){ //ファイル終端でなければ実行
            $part = explode(",", $Loadedline);
            if($part[0]==$country[1]) { //操作国データが見つかれば
                $IsControllCountryDataExist = 1;
                if ($IsControllCountryWon==0) $part[1]=$part[1]+1; //操作国が敗北した場合請求できる州を 1 つ追加 勝利した場合はそのまま
                //$file = file($filename);
                //unset($file[$i]); //その行全体を削除
                //file_put_contents($filename, $file);
                fwrite ($fp, $country[1].$part[1].$turn.".\n");
                fseek($fp,0,SEEK_END); //ファイルポイントを末尾に持ってきてループを離脱
            }
            $i=$i+1;
            $Loadedline = fgets($fp);
        }
        if($IsControllCountryDataExist=0){//操作国データが存在しなければ
            if ($IsControllCountryWon==1){
                fwrite ($fp, $country[0].0.$turn.".\n"); //外交データを追記
            }else{
                fwrite ($fp, $country[0].1.$turn.".\n"); //請求できる州を 1 つ追加した外交データを追記
            }   
        }
        fclose($fp);

        if($IsControllCountryWon==1) print "Victory";
        else print "Defeat";        
}else{
    print "Error!";
}
?>