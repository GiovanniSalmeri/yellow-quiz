# Quiz 0.8.16

Self-assessment tests (multiple-choice and true/false).

<p align="center"><img src="quiz-screenshot.png?raw=true" alt="Screenshot"></p>

## How to create a test

Define the test in a text file with whichever filename you like and put it into `media/quiz/`. There are three kinds of lines you can use.

+ Question line: it is in the form of a list item in Markdown (it begins for example with `2. ` or `+ `) and contains the question and the answers from which to choose, all separated by a `|`. The right answer must be put in first position (in the webpage the answers will be shown shuffled). The number of answers need not be the same in each question of the test. If you want to define a true/false item, instead of the answers put `1` if the item is true or `0` if it is false. Question lines in the form of a unordered list item (i.e. beginning with `* `, `+ `, or `- `) are automatically numbered: so use this form in order to avoid manually renumbering the questions after editing or rearranging them.
+ Options line: it begins with the `=` sign and contains (divided by a comma) the score for right answers, the score for wrong answers, the maximum number of minutes to complete the test (`0` means no limit of time, `%` means as many minutes as there are questions). When the score for wrong answers has the special value `%`, a proper value is calculated for each question in order to neutralise the effect of casual answers. The default for the options line is `+1,%,%`.
+ Literal line: any other line is reproduced as it is (for example in order to divide with headings several groups of questions, or provide a text or image which is the topic of the following questions).

In question lines and literal lines you can use `*` for italic, `**` for bold, `##`, `##` etc. for headings, `[text](URL)` for linking, `![text](URL)` for images, , `\n` for newline. Other URLs and email addresses are autolinked. White lines are ignored. 

See below an example.

## How to show a test

After defining the test, create a `[quiz]` shortcut.

The following compulsory argument is available:

`Location` = filename of the test to show  

This will create a form in which the defined test can be filled in. If a maximum time is set, a stopwatch will be shown. After signalling the completion of the test (or when the time available is elapsed), the corrected test and the resulting score are shown.

## Example

A complete example of a test definition:

```
=1,-0.25,10
## Informatics
+ Which is the best Content Management System in the world? | Yellow | Wordpress | Drupal | Joomla
+ The extension Quiz creates very easily both true/false and multiple-choice tests | 1
+ What are in informatics *strings*? | Sequences of characters | Electronic instruments
## Grammar
+ What is the word *the*? | An article | A noun | A verb | An adjective
+ Which is the plural of *mouse*? | Mice | Mouses | Cats
## History
All human beings are born free and equal in dignity and rights. They are endowed with reason and conscience and should act towards one another in a spirit of brotherhood. —*The Universal Declaration of Human Rights*, art. 1
+ When was this text written? | 1948 | 1492 | 1968 | 753 b.C.
+ What does the expression *human beings* refer to? | To men and women together | Only to men | Only to women | Only to children | To men, women, and pets
```

Showing a test:

    [quiz test.txt]

## Settings

The following setting can be configured in file `system/extensions/yellow-system.ini`:

`QuizDirectory` = (default: `media/quiz/`) = directory for test definitions
  
## Installation

[Download extension](https://github.com/GiovanniSalmeri/yellow-quiz/archive/master.zip) and copy zip file into your `system/extensions` folder. Right click if you use Safari.

## Developer

Giovanni Salmeri. [Get help](https://datenstrom.se/yellow/help/)
