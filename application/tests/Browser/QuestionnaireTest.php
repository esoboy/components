<?php

/*
 * Questionnaire on the AI page: one question at a time, a required one blocks Next,
 * letters pick choices, and Submit sends the answers.
 */

function question(): string
{
    return "[...document.querySelectorAll('[data-slot=\"questionnaire-item\"]')].find(item => item.checkVisibility())?.querySelector('[data-slot=\"questionnaire-title\"]')?.textContent.trim()";
}

it('shows the first question with its progress', function () {
    visit('/ai')
        ->assertScript(question(), 'What should we prototype next?')
        ->assertSee('Question 1 of 3')
        ->assertNoJavaScriptErrors();
});

it('blocks Next on a required question until it is answered', function () {
    visit('/ai')
        ->click('button:has-text("Next")')
        ->assertSee('Choose an answer to continue.')
        ->assertScript(question(), 'What should we prototype next?')
        ->click('[data-slot="questionnaire-choice"]:has-text("Question prompts")')
        ->click('button:has-text("Next")')
        ->assertScript(question(), 'What context should the agent inspect?')
        ->assertSee('Question 2 of 3');
});

it('picks choices with letter shortcuts and submits the answers', function () {
    visit('/ai')
        ->keys('[data-slot="questionnaire-choice"] input >> nth=0', 'c')
        ->assertScript("document.querySelector('[data-slot=\"questionnaire-choice\"] input[value=\"both\"]').checked")
        ->click('button:has-text("Next")')
        ->click('[data-slot="questionnaire-choice"]:has-text("Existing tests")')
        ->click('[data-slot="questionnaire-choice"]:has-text("Relevant source files")')
        ->click('button:has-text("Next")')
        ->click('button:has-text("Skip")')
        ->click('button:has-text("Submit")')
        ->assertSeeIn('#result', '"direction":"both"')
        ->assertScript("JSON.parse(document.getElementById('result').textContent).sources.sort().join()", 'files,tests')
        ->assertSeeIn('#result', '"depth":null');
});
