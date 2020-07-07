//JQuery使って属性を取得する、JSON.parseで扱える形に変換
var $script = $('#script');
var result = JSON.parse($script.attr('data-param');
//確認
console.log(result);