<h2>{$page_title}</h2>

{capture assign=button_text}{t}Sign in{/t}{/capture}
{render partial="shared/generic_form" button_text=$button_text}

<p>
{a action="password_recoveries/create_new"}{t}Forgot password?{/t}{/a}
</p>
