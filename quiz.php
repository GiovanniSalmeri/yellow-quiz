<?php
// Quiz extension, https://github.com/GiovanniSalmeri/yellow-quiz

class YellowQuiz {
    const VERSION = "0.8.16";
    public $yellow;         //access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("quizDirectory", "media/quiz/");
    }
    
    // Handle page content of shortcut
    public function onParseContentShortcut($page, $name, $text, $type) {
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
                $output .= "<form id=\"quiz-form\" method=\"post\" action=\"".$this->yellow->page->getUrl()."\">\n"; 
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
            $extensionLocation = $this->yellow->system->get("coreServerBase").$this->yellow->system->get("coreExtensionLocation");
            $output .= "<script type=\"text/javascript\" defer=\"defer\" src=\"{$extensionLocation}quiz.js\"></script>\n";
            $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$extensionLocation}quiz.css\" />\n";
        }
        return $output;
    }
}
