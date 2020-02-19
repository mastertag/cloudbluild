<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Detecta quantidade de pivos de alta de baixa, bem como a quantidade de vezes que teve um bloco sequinte confirmando o pivo
Route::get('/teste1', function () {

    function pctDe($valor, $pct) {
        $resposta = $valor * ($pct / 100);
        return $resposta;
    }

    // Auxilia a calcular a % de um valor sobre outro maior
    function pctDoValor($valor, $valorTotal) {
        $resposta = ($valor * 100) / $valorTotal;
        return $resposta;
    }

    function deAlta($array) {
        return $array[1] < $array[4];
    }

    function deBaixa($array) {
        return $array[1] > $array[4];
    }

    //verificar se aqui é um pivo de baixa
    function vaiCair($trecho) {
        //$primeiro = $trecho[0];
        $segundo = $trecho[1];
        $meio = $trecho[2]; // candle fo meio
        $atual = $trecho[3];

        $ladoEsquerdo = deAlta($segundo) ? $segundo[1] : $segundo[4];
        $centro = deAlta($meio) ? $meio[4] : $meio[1];
        $ladoDireito = deAlta($atual) ? $atual[1] : $atual[4];
        if ($centro > $ladoEsquerdo && $centro > $ladoDireito) {
            return true;
        }
        return false;
    }

    // se esse bloco é o bloco seguinte ao sinal de queda
    function caiu1Bloco($trecho) {
        $primeiro = $trecho[0];
        $segundo = $trecho[1];
        $terceiro = $trecho[2];
        $atual = $trecho[3];

        $novoArrayTeste = [
            [], //lixo
            $primeiro,
            $segundo,
            $terceiro
        ];
        $ANTES_VAI_CAIR = vaiCair($novoArrayTeste);
        if ($ANTES_VAI_CAIR && deBaixa($atual)) {
            return true;
        }
        return false;
    }


    //verificar se aqui é um pivo de alta
    function vaiSubir($trecho) {
        //$primeiro = $trecho[0];
        $segundo = $trecho[1];
        $meio = $trecho[2]; // candle fo meio
        $atual = $trecho[3];

        $ladoEsquerdo = deBaixa($segundo) ? $segundo[1] : $segundo[4];
        $centro = deBaixa($meio) ? $meio[4] : $meio[1];
        $ladoDireito = deBaixa($atual) ? $atual[1] : $atual[4];
        if ($centro < $ladoEsquerdo && $centro < $ladoDireito) {
            return true;
        }
        return false;
    }

    // se esse bloco é o bloco seguinte ao sinal de alta
    function subiu1Bloco($trecho) {
        $primeiro = $trecho[0];
        $segundo = $trecho[1];
        $terceiro = $trecho[2];
        $atual = $trecho[3];

        $novoArrayTeste = [
            [], //lixo
            $primeiro,
            $segundo,
            $terceiro
        ];
        $ANTES_VAI_SUBIR = vaiSubir($novoArrayTeste);
        if ($ANTES_VAI_SUBIR && deAlta($atual)) {
            return true;
        }
        return false;
    }


    function formatar($texto) {
        $partes = explode(';', $texto);
        $momento = $partes[0];
        $partes[4] = str_replace("\n", '', $partes[4]);
        $partes[4] = str_replace("\r", '', $partes[4]);
        $abertura = $partes[1];
        $maxima = $partes[2];
        $minima = $partes[3];
        $fechamento = $partes[4];
        return [
            $momento,
            $abertura,
            $maxima,
            $minima,
            $fechamento
        ];
    }

    $quantidadePontos = 80; //pontos (17R)
    //$quantidadePontos=120; //pontos (25R)
    //$quantidadePontos=15; //pontos (4R)
    //$quantidadePontos=50; //pontos (11R)
    //$quantidadePontos=245; //pontos (50R)
    $tick = 0.20;
    $contratos = 1;
    $pontos = $contratos * $tick;
    //$arquivo = file(storage_path('app/17R.csv'));
    //$arquivo = file(storage_path('app/25R.csv'));
    //$arquivo = file(storage_path('app/4R.csv'));
    //$arquivo = file(storage_path('app/11R.csv'));
    //$arquivo = file(storage_path('app/50R.csv'));
    $arquivo = file(storage_path('app/17R-12.csv'));
    array_shift($arquivo);
    $arquivo = array_reverse($arquivo);


    // Agrupar por dia
    $arquivo = collect($arquivo)->groupBy(function ($linha) {
        $partes = formatar($linha);
        $data = new \MasterTag\DataHora($partes[0]);
        return $data->dataCompleta();
    });

    $quantidadePivoBaixa_TOTAL = 0;
    $quantidadePivoBaixa1Bloco_TOTAL = 0;

    $quantidadePivoAlta_TOTAL = 0;
    $quantidadePivoAlta1Bloco_TOTAL = 0;

    foreach ($arquivo as $dia => $lista) {
        echo "<h1>$dia</h1><br>";

        $quantidadePivoBaixa = 0;
        $quantidadePivoBaixa1Bloco = 0;

        $quantidadePivoAlta = 0;
        $quantidadePivoAlta1Bloco = 0;

        $trecho = []; //treço dos ultimos 4
        foreach ($lista as $index => $linha) {
            $partes = formatar($linha);

            $momento = $partes[0];
            $abertura = $partes[1];
            //$maxima = $partes[2];
            //$minima = $partes[3];
            $fechamento = $partes[4];

            $blocoAtual = abs($abertura - $fechamento);
            if ($blocoAtual != $quantidadePontos) {
                echo "o bloco de $momento foi pulado pq a quantidade foi $blocoAtual que é diferente de $quantidadePontos<br>";
                continue;
            }


            $trecho[] = $partes;
            if (count($trecho) > 4) {
                array_shift($trecho);

                if (vaiSubir($trecho)) {
                    $quantidadePivoAlta++;
                    $quantidadePivoAlta_TOTAL++;
                    //echo "(index $index): iniciou pivô de alta no bloco ". ($index+1)." em $momento<br>";
                    //echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }
                if (subiu1Bloco($trecho)) {
                    $quantidadePivoAlta1Bloco++;
                    $quantidadePivoAlta1Bloco_TOTAL++;
                    //echo "(index $index): Alta no bloco ". ($index+1)." em $momento<br>";
                    //echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }
                // ********************************************************* BAIXA ****************************************************
                if (vaiCair($trecho)) {
                    $quantidadePivoBaixa++;
                    $quantidadePivoBaixa_TOTAL++;
                    //echo "(index $index): iniciou pivô de baixa no bloco ". ($index+1)." em $momento<br>";
                    //echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }

                if (caiu1Bloco($trecho)) {
                    $quantidadePivoBaixa1Bloco++;
                    $quantidadePivoBaixa1Bloco_TOTAL++;
                    //echo "(index $index): Queda no bloco ". ($index+1)." em $momento<br>";
                    //echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }
            }


        }


        if ($quantidadePivoAlta) {
            echo "Pivot de alta: $quantidadePivoAlta vezes<br>";
            echo "1 Bloco de alta logo em seguida: $quantidadePivoAlta1Bloco vezes<br>";
            $totalDePontos = $quantidadePivoAlta1Bloco * $quantidadePontos;
            $pctAcerto = number_format(pctDoValor($quantidadePivoAlta1Bloco, $quantidadePivoAlta), 2, ",", ".");
            echo "Isso é um total de $totalDePontos pontos ou R$ " . number_format($totalDePontos * $pontos, 2, ",", ".") . " reais.
Uma taxa de $pctAcerto % de acerto das vezes que tem um pivô de alta <br>";

            echo "--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>";
        }
        if ($quantidadePivoBaixa) {
            echo "Pivot de baixa: $quantidadePivoBaixa vezes<br>";
            echo "1 Bloco de baixa logo em seguida: $quantidadePivoBaixa1Bloco vezes<br>";
            $totalDePontos = $quantidadePivoBaixa1Bloco * $quantidadePontos;
            $pctAcerto = number_format(pctDoValor($quantidadePivoBaixa1Bloco, $quantidadePivoBaixa), 2, ",", ".");
            echo "Isso é um total de $totalDePontos pontos ou R$ " . number_format($totalDePontos * $pontos, 2, ",", ".") . " reais.
Uma taxa de $pctAcerto % de acerto das vezes que tem um pivô de baixa <br>";

            echo "--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>";
        }

    }

    echo "===================================================================================================================<br>";
    echo "===================================================================================================================<br>";

    if ($quantidadePivoAlta_TOTAL) {
        echo "Pivot de alta: $quantidadePivoAlta_TOTAL vezes<br>";
        echo "1 Bloco de alta logo em seguida: $quantidadePivoAlta1Bloco_TOTAL vezes<br>";
        $totalDePontos = $quantidadePivoAlta1Bloco_TOTAL * $quantidadePontos;
        $pctAcerto = number_format(pctDoValor($quantidadePivoAlta1Bloco_TOTAL, $quantidadePivoAlta_TOTAL), 2, ",", ".");
        echo "Isso é um total de $totalDePontos pontos ou R$ " . number_format($totalDePontos * $pontos, 2, ",", ".") . " reais.
Uma taxa de $pctAcerto % de acerto das vezes que tem um pivô de alta <br>";
        echo "--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>";
    }
    if ($quantidadePivoBaixa_TOTAL) {
        echo "Pivot de baixa: $quantidadePivoBaixa_TOTAL vezes<br>";
        echo "1 Bloco de baixa logo em seguida: $quantidadePivoBaixa1Bloco_TOTAL vezes<br>";
        $totalDePontos = $quantidadePivoBaixa1Bloco_TOTAL * $quantidadePontos;
        $pctAcerto = number_format(pctDoValor($quantidadePivoBaixa1Bloco_TOTAL, $quantidadePivoBaixa_TOTAL, 2, ",", "."));
        echo "Isso é um total de $totalDePontos pontos ou R$ " . number_format($totalDePontos * $pontos, 2, ",", ".") . " reais.
Uma taxa de $pctAcerto % de acerto das vezes que tem um pivô de baixa <br>";
    }


});
// detecta os pivos e anda a favor da comra ou da venda com stop móvel
Route::get('/teste2', function () {

    function pctDe($valor, $pct) {
        $resposta = $valor * ($pct / 100);
        return $resposta;
    }

    // Auxilia a calcular a % de um valor sobre outro maior
    function pctDoValor($valor, $valorTotal) {
        $resposta = ($valor * 100) / $valorTotal;
        return $resposta;
    }

    function deAlta($array) {
        return $array[1] < $array[4];
    }

    function deBaixa($array) {
        return $array[1] > $array[4];
    }

    //verificar se aqui é um pivo de baixa
    function vaiCair($trecho) {
        //$primeiro = $trecho[0];
        $segundo = $trecho[1];
        $meio = $trecho[2]; // candle fo meio
        $atual = $trecho[3];

        $ladoEsquerdo = deAlta($segundo) ? $segundo[1] : $segundo[4];
        $centro = deAlta($meio) ? $meio[4] : $meio[1];
        $ladoDireito = deAlta($atual) ? $atual[1] : $atual[4];
        if ($centro > $ladoEsquerdo && $centro > $ladoDireito) {
            return true;
        }
        return false;
    }

    // se esse bloco é o bloco seguinte ao sinal de queda
    function caiu1Bloco($trecho) {
        $primeiro = $trecho[0];
        $segundo = $trecho[1];
        $terceiro = $trecho[2];
        $atual = $trecho[3];

        $novoArrayTeste = [
            [], //lixo
            $primeiro,
            $segundo,
            $terceiro
        ];
        $ANTES_VAI_CAIR = vaiCair($novoArrayTeste);
        if ($ANTES_VAI_CAIR && deBaixa($atual)) {
            return true;
        }
        return false;
    }


    //verificar se aqui é um pivo de alta
    function vaiSubir($trecho) {
        //$primeiro = $trecho[0];
        $segundo = $trecho[1];
        $meio = $trecho[2]; // candle fo meio
        $atual = $trecho[3];

        $ladoEsquerdo = deBaixa($segundo) ? $segundo[1] : $segundo[4];
        $centro = deBaixa($meio) ? $meio[4] : $meio[1];
        $ladoDireito = deBaixa($atual) ? $atual[1] : $atual[4];
        if ($centro < $ladoEsquerdo && $centro < $ladoDireito) {
            return true;
        }
        return false;
    }

    // se esse bloco é o bloco seguinte ao sinal de alta
    function subiu1Bloco($trecho) {
        $primeiro = $trecho[0];
        $segundo = $trecho[1];
        $terceiro = $trecho[2];
        $atual = $trecho[3];

        $novoArrayTeste = [
            [], //lixo
            $primeiro,
            $segundo,
            $terceiro
        ];
        $ANTES_VAI_SUBIR = vaiSubir($novoArrayTeste);
        if ($ANTES_VAI_SUBIR && deAlta($atual)) {
            return true;
        }
        return false;
    }


    function formatar($texto) {
        $partes = explode(';', $texto);
        $momento = $partes[0];
        $partes[4] = str_replace("\n", '', $partes[4]);
        $partes[4] = str_replace("\r", '', $partes[4]);
        $abertura = $partes[1];
        $maxima = $partes[2];
        $minima = $partes[3];
        $fechamento = $partes[4];
        return [
            $momento,
            $abertura,
            $maxima,
            $minima,
            $fechamento
        ];
    }

    //$quantidadePontos=15; //pontos (4R)
    $quantidadePontos = 50; //pontos (11R)
    //$quantidadePontos=80; //pontos (17R)
    //$quantidadePontos=100; //pontos (21R)
    //$quantidadePontos=120; //pontos (25R)
    //$quantidadePontos=160; //pontos (33R)
    //$quantidadePontos=195; //pontos (40R)
    //$quantidadePontos=220; //pontos (45R)
    //$quantidadePontos=245; //pontos (50R)
    $tick = 0.20;
    $contratos = 1;
    $pontos = $contratos * $tick;
    //$arquivo = file(storage_path('app/4R.csv'));
    $arquivo = file(storage_path('app/11R.csv'));
    //$arquivo = file(storage_path('app/17R.csv'));
    //$arquivo = file(storage_path('app/21R.csv'));
    //$arquivo = file(storage_path('app/25R.csv'));
    //$arquivo = file(storage_path('app/33R.csv'));
    //$arquivo = file(storage_path('app/40R.csv'));
    //$arquivo = file(storage_path('app/45R.csv'));
    //$arquivo = file(storage_path('app/50R.csv'));
    //$arquivo = file(storage_path('app/17R-12.csv'));
    //$arquivo = file(storage_path('app/17R-23-01-2020.csv'));
    array_shift($arquivo);
    $arquivo = array_reverse($arquivo);


    // Agrupar por dia
    $arquivo = collect($arquivo)->groupBy(function ($linha) {
        $partes = formatar($linha);
        $data = new \MasterTag\DataHora($partes[0]);
        return $data->dataCompleta();
    });

    $quantidadePivoBaixa_TOTAL = 0;
    $quantidadePivoBaixa1Bloco_TOTAL = 0;

    $quantidadePivoAlta_TOTAL = 0;
    $quantidadePivoAlta1Bloco_TOTAL = 0;

    $totalDePontos_TOTAL = 0;

    foreach ($arquivo as $dia => $lista) {
        echo "<h1>$dia</h1>";

        $quantidadePivoBaixa = 0;
        $quantidadePivoBaixa1Bloco = 0;

        $quantidadePivoAlta = 0;
        $quantidadePivoAlta1Bloco = 0;

        $VENDIDO = FALSE;
        $COMPRADO = FALSE;
        $POSICAO_ABERTA = FALSE;
        $stopAtual = 0;
        $totalDePontos = 0;


        $trecho = []; //treço dos ultimos 4
        foreach ($lista as $index => $linha) {
            $partes = formatar($linha);

            $momento = $partes[0];
            $abertura = $partes[1];
            //$maxima = $partes[2];
            //$minima = $partes[3];
            $fechamento = $partes[4];

            $blocoAtual = abs($abertura - $fechamento);
            if ($blocoAtual != $quantidadePontos) {
                echo "o bloco de $momento foi pulado pq a quantidade foi $blocoAtual que é diferente de $quantidadePontos<br>";
                continue;
            }


            $trecho[] = $partes;
            if (count($trecho) >= 4) {

                //Abre uma compra
                if (vaiSubir($trecho) && !$POSICAO_ABERTA) {
                    $quantidadePivoAlta++;
                    $quantidadePivoAlta_TOTAL++;
                    echo "(index $index): iniciou pivô de alta no bloco " . ($index + 1) . " em $momento<br>";

                    if (!$POSICAO_ABERTA) {
                        $POSICAO_ABERTA = true;// entra na compra
                        $COMPRADO = TRUE;
                        $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                    }
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }
                // Stop uma venda
                if (deAlta($partes) && $POSICAO_ABERTA && $VENDIDO) {
                    $totalDePontos -= $quantidadePontos;
                    echo "(index $index): movimento <b>contra na venda</b> no bloco" . ($index + 1) . " em $momento. Fui stopado em $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    $stopAtual = 0; // abertura do candle anterior
                    $POSICAO_ABERTA = FALSE;
                    $VENDIDO = FALSE;

                    // testar se é um pivo de alta
                    if (vaiSubir($trecho) && !$POSICAO_ABERTA) {
                        $quantidadePivoAlta++;
                        $quantidadePivoAlta_TOTAL++;
                        echo "(index $index): iniciou pivô de alta no bloco " . ($index + 1) . " em $momento<br>";

                        if (!$POSICAO_ABERTA) {
                            $POSICAO_ABERTA = true;// entra na compra
                            $COMPRADO = TRUE;
                            $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                        }
                        echo "------------------------------------------------------------------------------------------------<br><br>";
                        continue;
                    }
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;

                }
                // Avança na compra
                if (deAlta($partes) && $POSICAO_ABERTA && $COMPRADO) {
                    $totalDePontos += $quantidadePontos;
                    $stopAtual = $trecho[2][1]; // abertura do candle anterior

                    echo "(index $index): movimento a favor da compra no bloco" . ($index + 1) . " em $momento. O stop agora é $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;

                }

                // ********************************************************* BAIXA ****************************************************
                //Abre uma venda
                if (vaiCair($trecho) && !$POSICAO_ABERTA) {
                    $quantidadePivoBaixa++;
                    $quantidadePivoBaixa_TOTAL++;
                    echo "(index $index): iniciou pivô de baixa no bloco " . ($index + 1) . " em $momento<br>";
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    if (!$POSICAO_ABERTA) {
                        $POSICAO_ABERTA = true;// entra na venda
                        $VENDIDO = TRUE;
                        $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                    }
                    continue;
                }
                //Stop uma compra
                if (deBaixa($partes) && $POSICAO_ABERTA && $COMPRADO) {
                    $totalDePontos -= $quantidadePontos;
                    //$quantidadePivoBaixa++;
                    //$quantidadePivoBaixa_TOTAL++;
                    echo "(index $index): movimento <b>contra na compra</b> no bloco" . ($index + 1) . " em $momento. Fui stopado em $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    $stopAtual = 0; // abertura do candle anterior
                    $POSICAO_ABERTA = FALSE;
                    $COMPRADO = FALSE;

                    // testar se é um pivo de baixa
                    if (vaiCair($trecho) && !$POSICAO_ABERTA) {
                        $quantidadePivoBaixa++;
                        $quantidadePivoBaixa_TOTAL++;
                        echo "(index $index): iniciou pivô de baixa no bloco " . ($index + 1) . " em $momento<br>";
                        echo "------------------------------------------------------------------------------------------------<br><br>";
                        if (!$POSICAO_ABERTA) {
                            $POSICAO_ABERTA = true;// entra na compra
                            $VENDIDO = TRUE;
                            $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                        }
                        continue;
                    }
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }

                //Avança ne venda
                if (deBaixa($partes) && $POSICAO_ABERTA && $VENDIDO) {
                    $totalDePontos += $quantidadePontos;
                    $stopAtual = $trecho[2][1]; // abertura do candle anterior

                    echo "(index $index): movimento a favor da venda no bloco" . ($index + 1) . " em $momento. O stop agora é $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }

                array_shift($trecho);

            }

        }

        echo "<h4> Total (parcial): $totalDePontos pontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.');
        $totalDePontos_TOTAL += $totalDePontos;
        $totalDePontos = 0;
    }

    echo "<br><br>===================================================================================================================<br>";
    echo "===================================================================================================================<br>";
    echo "<h4> Total: $totalDePontos_TOTAL pontos ou R$ " . number_format($totalDePontos_TOTAL * $pontos, 2, ',', '.');


});

// detecta os pivos e testar se tem mais lucro depois de cada duas entradas, ou 1
Route::get('/teste3', function () {

    function pctDe($valor, $pct) {
        $resposta = $valor * ($pct / 100);
        return $resposta;
    }

    // Auxilia a calcular a % de um valor sobre outro maior
    function pctDoValor($valor, $valorTotal) {
        $resposta = ($valor * 100) / $valorTotal;
        return $resposta;
    }

    function deAlta($array) {
        return $array[1] < $array[4];
    }

    function deBaixa($array) {
        return $array[1] > $array[4];
    }

    //verificar se aqui é um pivo de baixa
    function vaiCair($trecho) {
        //$primeiro = $trecho[0];
        $segundo = $trecho[1];
        $meio = $trecho[2]; // candle fo meio
        $atual = $trecho[3];

        $ladoEsquerdo = deAlta($segundo) ? $segundo[1] : $segundo[4];
        $centro = deAlta($meio) ? $meio[4] : $meio[1];
        $ladoDireito = deAlta($atual) ? $atual[1] : $atual[4];
        if ($centro > $ladoEsquerdo && $centro > $ladoDireito) {
            return true;
        }
        return false;
    }

    // se esse bloco é o bloco seguinte ao sinal de queda
    function caiu1Bloco($trecho) {
        $primeiro = $trecho[0];
        $segundo = $trecho[1];
        $terceiro = $trecho[2];
        $atual = $trecho[3];

        $novoArrayTeste = [
            [], //lixo
            $primeiro,
            $segundo,
            $terceiro
        ];
        $ANTES_VAI_CAIR = vaiCair($novoArrayTeste);
        if ($ANTES_VAI_CAIR && deBaixa($atual)) {
            return true;
        }
        return false;
    }


    //verificar se aqui é um pivo de alta
    function vaiSubir($trecho) {
        //$primeiro = $trecho[0];
        $segundo = $trecho[1];
        $meio = $trecho[2]; // candle fo meio
        $atual = $trecho[3];

        $ladoEsquerdo = deBaixa($segundo) ? $segundo[1] : $segundo[4];
        $centro = deBaixa($meio) ? $meio[4] : $meio[1];
        $ladoDireito = deBaixa($atual) ? $atual[1] : $atual[4];
        if ($centro < $ladoEsquerdo && $centro < $ladoDireito) {
            return true;
        }
        return false;
    }

    // se esse bloco é o bloco seguinte ao sinal de alta
    function subiu1Bloco($trecho) {
        $primeiro = $trecho[0];
        $segundo = $trecho[1];
        $terceiro = $trecho[2];
        $atual = $trecho[3];

        $novoArrayTeste = [
            [], //lixo
            $primeiro,
            $segundo,
            $terceiro
        ];
        $ANTES_VAI_SUBIR = vaiSubir($novoArrayTeste);
        if ($ANTES_VAI_SUBIR && deAlta($atual)) {
            return true;
        }
        return false;
    }


    function formatar($texto) {
        $partes = explode(';', $texto);
        $momento = $partes[0];
        $partes[4] = str_replace("\n", '', $partes[4]);
        $partes[4] = str_replace("\r", '', $partes[4]);
        $abertura = $partes[1];
        $maxima = $partes[2];
        $minima = $partes[3];
        $fechamento = $partes[4];
        return [
            $momento,
            $abertura,
            $maxima,
            $minima,
            $fechamento
        ];
    }

    //$quantidadePontos=15; //pontos (4R)
    $quantidadePontos = 50; //pontos (11R)
    //$quantidadePontos=80; //pontos (17R)
    //$quantidadePontos=100; //pontos (21R)
    //$quantidadePontos=120; //pontos (25R)
    //$quantidadePontos=160; //pontos (33R)
    //$quantidadePontos=195; //pontos (40R)
    //$quantidadePontos=220; //pontos (45R)
    //$quantidadePontos=245; //pontos (50R)
    $tick = 0.20;
    $contratos = 1;
    $pontos = $contratos * $tick;
    //$arquivo = file(storage_path('app/4R.csv'));
    $arquivo = file(storage_path('app/11R.csv'));
    //$arquivo = file(storage_path('app/17R.csv'));
    //$arquivo = file(storage_path('app/21R.csv'));
    //$arquivo = file(storage_path('app/25R.csv'));
    //$arquivo = file(storage_path('app/33R.csv'));
    //$arquivo = file(storage_path('app/40R.csv'));
    //$arquivo = file(storage_path('app/45R.csv'));
    //$arquivo = file(storage_path('app/50R.csv'));
    //$arquivo = file(storage_path('app/17R-12.csv'));
    //$arquivo = file(storage_path('app/17R-23-01-2020.csv'));
    array_shift($arquivo);
    $arquivo = array_reverse($arquivo);


    // Agrupar por dia
    $arquivo = collect($arquivo)->groupBy(function ($linha) {
        $partes = formatar($linha);
        $data = new \MasterTag\DataHora($partes[0]);
        return $data->dataCompleta();
    });

    $quantidadePivoBaixa_TOTAL = 0;
    $quantidadePivoBaixa1Bloco_TOTAL = 0;

    $quantidadePivoAlta_TOTAL = 0;
    $quantidadePivoAlta1Bloco_TOTAL = 0;

    $totalDePontos_TOTAL = 0;

    foreach ($arquivo as $dia => $lista) {
        echo "<h1>$dia</h1>";

        $quantidadePivoBaixa = 0;
        $quantidadePivoBaixa1Bloco = 0;

        $quantidadePivoAlta = 0;
        $quantidadePivoAlta1Bloco = 0;

        $VENDIDO = FALSE;
        $COMPRADO = FALSE;
        $POSICAO_ABERTA = FALSE;
        $stopAtual = 0;
        $totalDePontos = 0;


        $trecho = []; //treço dos ultimos 4
        foreach ($lista as $index => $linha) {
            $partes = formatar($linha);

            $momento = $partes[0];
            $abertura = $partes[1];
            //$maxima = $partes[2];
            //$minima = $partes[3];
            $fechamento = $partes[4];

            $blocoAtual = abs($abertura - $fechamento);
            if ($blocoAtual != $quantidadePontos) {
                echo "o bloco de $momento foi pulado pq a quantidade foi $blocoAtual que é diferente de $quantidadePontos<br>";
                continue;
            }


            $trecho[] = $partes;
            if (count($trecho) >= 4) {

                //Abre uma compra
                if (vaiSubir($trecho) && !$POSICAO_ABERTA) {
                    $quantidadePivoAlta++;
                    $quantidadePivoAlta_TOTAL++;
                    echo "(index $index): iniciou pivô de alta no bloco " . ($index + 1) . " em $momento<br>";

                    if (!$POSICAO_ABERTA) {
                        $POSICAO_ABERTA = true;// entra na compra
                        $COMPRADO = TRUE;
                        $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                    }
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }
                // Stop uma venda
                if (deAlta($partes) && $POSICAO_ABERTA && $VENDIDO) {
                    $totalDePontos -= $quantidadePontos;
                    echo "(index $index): movimento <b>contra na venda</b> no bloco" . ($index + 1) . " em $momento. Fui stopado em $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    $stopAtual = 0; // abertura do candle anterior
                    $POSICAO_ABERTA = FALSE;
                    $VENDIDO = FALSE;

                    // testar se é um pivo de alta
                    if (vaiSubir($trecho) && !$POSICAO_ABERTA) {
                        $quantidadePivoAlta++;
                        $quantidadePivoAlta_TOTAL++;
                        echo "(index $index): iniciou pivô de alta no bloco " . ($index + 1) . " em $momento<br>";

                        if (!$POSICAO_ABERTA) {
                            $POSICAO_ABERTA = true;// entra na compra
                            $COMPRADO = TRUE;
                            $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                        }
                        echo "------------------------------------------------------------------------------------------------<br><br>";
                        continue;
                    }
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;

                }
                // Avança na compra
                if (deAlta($partes) && $POSICAO_ABERTA && $COMPRADO) {
                    $totalDePontos += $quantidadePontos;
                    $stopAtual = $trecho[2][1]; // abertura do candle anterior

                    echo "(index $index): movimento a favor da compra no bloco" . ($index + 1) . " em $momento. O stop agora é $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;

                }

                // ********************************************************* BAIXA ****************************************************
                //Abre uma venda
                if (vaiCair($trecho) && !$POSICAO_ABERTA) {
                    $quantidadePivoBaixa++;
                    $quantidadePivoBaixa_TOTAL++;
                    echo "(index $index): iniciou pivô de baixa no bloco " . ($index + 1) . " em $momento<br>";
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    if (!$POSICAO_ABERTA) {
                        $POSICAO_ABERTA = true;// entra na venda
                        $VENDIDO = TRUE;
                        $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                    }
                    continue;
                }
                //Stop uma compra
                if (deBaixa($partes) && $POSICAO_ABERTA && $COMPRADO) {
                    $totalDePontos -= $quantidadePontos;
                    //$quantidadePivoBaixa++;
                    //$quantidadePivoBaixa_TOTAL++;
                    echo "(index $index): movimento <b>contra na compra</b> no bloco" . ($index + 1) . " em $momento. Fui stopado em $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    $stopAtual = 0; // abertura do candle anterior
                    $POSICAO_ABERTA = FALSE;
                    $COMPRADO = FALSE;

                    // testar se é um pivo de baixa
                    if (vaiCair($trecho) && !$POSICAO_ABERTA) {
                        $quantidadePivoBaixa++;
                        $quantidadePivoBaixa_TOTAL++;
                        echo "(index $index): iniciou pivô de baixa no bloco " . ($index + 1) . " em $momento<br>";
                        echo "------------------------------------------------------------------------------------------------<br><br>";
                        if (!$POSICAO_ABERTA) {
                            $POSICAO_ABERTA = true;// entra na compra
                            $VENDIDO = TRUE;
                            $stopAtual = $trecho[2][4]; // fechamento do candle anterior
                        }
                        continue;
                    }
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }

                //Avança ne venda
                if (deBaixa($partes) && $POSICAO_ABERTA && $VENDIDO) {
                    $totalDePontos += $quantidadePontos;
                    $stopAtual = $trecho[2][1]; // abertura do candle anterior

                    echo "(index $index): movimento a favor da venda no bloco" . ($index + 1) . " em $momento. O stop agora é $stopAtual (total de $totalDePontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.') . ")<br>";
                    echo "------------------------------------------------------------------------------------------------<br><br>";
                    continue;
                }

                array_shift($trecho);

            }

        }

        echo "<h4> Total (parcial): $totalDePontos pontos ou R$ " . number_format($totalDePontos * $pontos, 2, ',', '.');
        $totalDePontos_TOTAL += $totalDePontos;
        $totalDePontos = 0;
    }

    echo "<br><br>===================================================================================================================<br>";
    echo "===================================================================================================================<br>";
    echo "<h4> Total: $totalDePontos_TOTAL pontos ou R$ " . number_format($totalDePontos_TOTAL * $pontos, 2, ',', '.');


});
