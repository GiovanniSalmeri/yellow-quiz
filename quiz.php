<?php
// Quiz extension, https://github.com/GiovanniSalmeri/yellow-quiz

class YellowQuiz {
    const VERSION = "0.9.1";
    public $yellow;         //access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("quizDirectory", "media/quiz/");
        $this->yellow->language->setDefaults(array(
            "Language: en",
            "QuizCorrected: Here is the corrected test: right answers are highlighted in <b>bold</b>, wrong answers are highlighted in <del class=\"multichoice-error\">red strikethrough</del>, at the end the score obtained is shown.",
            "QuizDunno: Don't know",
            "QuizButton: Correction and score",
            "QuizResult: Right answers: <b>@right_answers out of @curr_question</b>",
            "QuizScore: Score: <b>@score out of @max_score</b>",
            "QuizTrue: True",
            "QuizFalse: False",
            "Language: de",
            "QuizCorrected: Hier ist die Korrektur des Tests: die richtigen Antworten werden <b>fett</b> hervorgehoben, die falschen Antworten werden <del class=\"multichoice-error\">rot und durchgestrichen</del> hervorgehoben, am Ende wird die erreichte Gesamtpunktzahl angezeigt.",
            "QuizDunno: Ich weiß nicht",
            "QuizButton: Korrektur und Ergebnis",
            "QuizResult: Richtige Antworten: <b>@right_answers von @curr_question</b>",
            "QuizScore: Ergebnis: <b>@score von @max_score</b> Punkten",
            "QuizTrue: Wahr",
            "QuizFalse: Falsch",
            "Language: fr",
            "QuizCorrected: Vous trouverez ci-dessous le questionnaire correct&nbsp;: les bonnes réponses sont mises en évidence en <b>gras</b>, les mauvaises réponses sont mises en évidence en <del class=\"multichoice-error\">rouge barré</del>, à la fin le score total obtenu est indiqué.",
            "QuizDunno: Je ne sais pas",
            "QuizButton: Correction et résultat",
            "QuizResult: Bonnes rëponses: <b>@right_answers sur @curr_question</b>",
            "QuizScore: Score: <b>@score sur @max_score</b>",
            "QuizTrue: Vrai",
            "QuizFalse: Faux",
            "Language: it",
            "QuizCorrected: Di seguito è il questionario corretto: le risposte giuste sono evidenziate in <b>neretto</b>, le risposte sbagliate sono evidenziate in <del class=\"multichoice-error\">rosso barrato</del>, alla fine viene indicato il punteggio complessivo ottenuto.",
            "QuizDunno: Non so",
            "QuizButton: Correzione e risultato",
            "QuizResult: Risposte giuste: <b>@right_answers su @curr_question</b>",
            "QuizScore: Punteggio: <b>@score su @max_score</b>",
            "QuizTrue: Vero",
            "QuizFalse: Falso",
            "Language: es",
            "QuizCorrected: A continuación se muestra el cuestionario correcto: las respuestas acertadas se resaltan en <b>negrita</b>, las respuestas incorrectas se resaltan en <del class=\"multichoice-error\">rojo tachado</del>, al final se indica la puntuación total obtenida.",
            "QuizDunno: No sé",
            "QuizButton: Corrección y resultado",
            "QuizResult: Respuestas correctas: <b>@right_answers de @curr_question</b>",
            "QuizScore: Puntuación: <b>@score de @max_score</b>",
            "QuizTrue: Verdadero",
            "QuizFalse: Falso",
            "Language: nl",
            "QuizCorrected: Hieronder staat de gecorrigeerde vragenlijst: de juiste antwoorden zijn <b>vetgedrukt</b>, de verkeerde antwoorden zijn vetgedrukt in <del class=\"multichoice-error\">rood doorgestreept</del>, aan het eind wordt de totaal behaalde score aangegeven.",
            "QuizDunno: Weet niet",
            "QuizButton: Correctie en score",
            "QuizResult: Juiste antwoorden: <b>@right_answers van de @curr_question</b>",
            "QuizScore: Score: <b>@score op @max_score</b>",
            "QuizTrue: Waar",
            "QuizFalse: Onwaar",
            "Language: pt",
            "QuizCorrected: Abaixo está o questionário correto: as respostas certas são destacadas em <b>negrito</b>, as respostas erradas são destacadas em <del class=\"multichoice-error\">vermelho riscado</b>, no final a pontuação total obtida é indicada.",
            "QuizDunno: Não sei",
            "QuizButton: Correcção e pontuação",
            "QuizResult: Respostas certas: <b>@right_answers em @curr_question</b>",
            "QuizScore: Pontuação: <b>@score em @max_score</b>",
            "QuizTrue: Verdadeiro",
            "QuizFalse: Falso",
        ));
    }
    
    // Handle page content of shortcut
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = null;
        if ($name=="quiz" && ($type=="block" || $type=="inline")) {
            list($rightScore, $wrongScore, $time) = [1, '%', '%']; // default
            $tfStrings = [$this->yellow->language->getText("quizFalse"), $this->yellow->language->getText("quizTrue")];
            $dunnoString = $this->yellow->language->getText("quizDunno");
            $lines = @file($this->yellow->system->get("quizDirectory").$text);
            if (!$lines) return;
            $currQuestion = 0;
            $rightAnswers = $score = 0;
            $isResultPage = $this->yellow->page->getRequest('quest');
            if (!$isResultPage) {
                // add #quiz-correction if you use [quiz] shortcut low in the page
                $output .= "<form id=\"quiz-form\" method=\"post\" action=\"".$this->yellow->page->getUrl(true)."\">\n"; 
            } else {
                $output .= "<div id=\"quiz-correction\" class=\"notice1\">";
                $output .= $this->yellow->language->getText("quizCorrected");
                $output .= "</div>\n";
            }
            foreach ($lines as $line) {
                if (!trim($line)) { // line is blank
                    // ignore
                } elseif ($line[0]=="=") { // line contains parameters
                    list($rightScore, $wrongScore, $time) = array_map('trim', explode(",", substr($line, 1)));
                } elseif (preg_match('/^(\d+\.|[-+*])\s/', $line)) {
                    $questions = array_map('trim', explode('|', $line));
                    if (!is_numeric($questions[0][0])) $questions[0] = $currQuestion+1 . '. ' . substr($questions[0], 2);
                    $answerRange = range(1, count($questions)-1);
                    if (count($answerRange)==1 and ($questions[1]=='1' or $questions[1]=='0')) { // T/F
                        if ($questions[1]=='1') {
                            $answers = [1, 2];
                            $questions[1] = $tfStrings[1];
                            $questions[2] = $tfStrings[0];
                        } else {
                            $answers = [2, 1];
                            $questions[1] = $tfStrings[0];
                            $questions[2] = $tfStrings[1];
                        }
                        array_push($answerRange, 2);
                        $mcQuestion = false;
                    } else {
                        $mcQuestion = true;
                        // the degenerated case of a mcq with one answer is not treated particularly
                    }
                    if (!$isResultPage) { // is the page with the form
                        if ($mcQuestion) {
                            $answers = $answerRange;
                            shuffle($answers);
                        }
                        $answersComp = implode(",", $answers);
                        $output .= "<div><input type=\"hidden\" name=\"quest_ord[{$currQuestion}]\" value=\"{$answersComp}\" /></div>\n";
                        $output .= "<dl class=\"quiz\">\n<dt>".$this->toHTML($questions[0], false)."</dt>\n"; // is the question
                        foreach ($answerRange as $i) { // answers
                            $output .= "<dd><label><input type=\"radio\" value=\"{$i}\" name=\"quest[{$currQuestion}]\" />".$this->toHTML($questions[$answers[$i-1]], false)."</label></dd>\n";
                        }
                        $output .= "<dd><label><input checked=\"checked\" type=\"radio\" value=\"0\" name=\"quest[{$currQuestion}]\" />{$dunnoString}</label></dd>\n"; // at the end, with value 0
                        $output .= "</dl>\n";
                    } else { // is the page with the corrected test
                        $answers = explode(",", $this->yellow->page->getRequest('quest_ord')[$currQuestion]); // casting in int is automatic
                        $output .= "<dl class=\"quiz corrected\">\n<dt>".$this->toHTML($questions[0], false)."</dt>\n"; // is the question
                        $givenAnswer = $this->yellow->page->getRequest('quest')[$currQuestion];
                        foreach ($answerRange as $i) { // answers
                            if ($answers[$i-1]==1) { // if right
                                $output .= "<dd><b>".$this->toHTML($questions[$answers[$i-1]], false)."</b></dd>";
                                if ($givenAnswer==$i) { 
                                    $rightAnswers += 1; 
                                    $score += $rightScore; 
                                }
                            } elseif ($givenAnswer==$i) {
                                $output .= "<dd><del class=\"quiz-error\">".$this->toHTML($questions[$answers[$i-1]], false)."</del></dd>";
                                $score += ($wrongScore=='%' ? -$rightScore/(count($questions)-2) : $wrongScore);
                            } else {
                                $output .= "<dd>{$questions[$answers[$i-1]]}</dd>";
                            }
                        }
                        if ($givenAnswer==0) {
                            $output .= "<dd><del class=\"quiz-error\">{$dunnoString}</del></dd>";
                        } else {
                            $output .= "<dd>{$dunnoString}</dd>";
                        }
                        $output .= "</dl>";
                    }
                    $currQuestion += 1;
                } else { // line is literal
                    $output .= $this->toHTML($line, true);
                }
            }
            if (!$isResultPage) {
                $output .= "<p></p><p><input class=\"btn\" type=\"submit\" value=\"".$this->yellow->language->getText("quizButton")."\" /></p>\n";
                $output .= "</form>\n";
                if ($time=='%') $time = $currQuestion;
                if ($time > 0) {
                  $output .= "<div id=\"quiz-progress\">\n";
                  $output .= "<div id=\"quiz-progressbar\"></div>\n";
                  $output .= "<div id=\"quiz-progresstext\" data-time=\"{$time}\">{$time} min&nbsp;</div>\n";
                  $output .= "</div>";
                }
            } else {
                $maxScore = $currQuestion*$rightScore;
                $output .= "<div class=\"notice1\">";
                $output .= "<p>".str_replace(["@right_answers", "@curr_question"], [$rightAnswers, $currQuestion], $this->yellow->language->getText("quizResult"))."<br />";
                $output .= str_replace(["@score", "@max_score"], [round($score,1), $maxScore], $this->yellow->language->getText("quizScore"))."</p>";
                $output .= "</div>";
            }
        }
        return $output;
    }

    // Micro markdown-like formatting
    function toHTML($text, $p) {
        $text = htmlspecialchars($text);
        $text = preg_replace_callback('/\\\[\\\n]/', function($m) { return $m[0]=="\\\\" ? "\\" : "<br />\n"; }, $text);
        $text = preg_replace("/\*\*(.+?)\*\*/", "<b>$1</b>", $text);
        $text = preg_replace("/\*(.+?)\*/", "<i>$1</i>", $text);
        $text = preg_replace("/!\[(.*?)\]\((https?:\/\/[^ )]+)\)/", "<img src=\"$2\" alt=\"$1\" />", $text);
        $text = preg_replace("/\[(.*?)\]\((https?:\/\/[^ )]+)\)/", "<a href=\"$2\">$1</a>", $text);
        $text = preg_replace("/(?<!\()(https?:\/\/[^ )]+)(?!\))/", "<a href=\"$1\">$1</a>", $text);
        if ($text[0]=='#') {
            $text = preg_replace_callback('/^(#+)\s*(.*)/', function($m) { $h = strlen($m[1]); return "<h{$h}>".$m[2]."</h{$h}>"; }, $text);
        } elseif ($p) {
            $text = "<p>".$text."</p>";
        }
        return $text;
    }

    // Handle page extra data
    public function onParsePageExtra($page, $name) {
        $output = null;
        if ($name=="header") {
            $assetLocation = $this->yellow->system->get("coreServerBase").$this->yellow->system->get("coreAssetLocation");
            $output .= "<script type=\"text/javascript\" defer=\"defer\" src=\"{$assetLocation}quiz.js\"></script>\n";
            $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$assetLocation}quiz.css\" />\n";
        }
        return $output;
    }
}
