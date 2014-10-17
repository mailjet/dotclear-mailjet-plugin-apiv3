<?php

/*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */

if (!defined('DC_RC_PATH')) { return; }

require dirname(__FILE__).'/_widgets.php';

class publicMailjetSubscribeWidget
{
    public static function mailjetSubscribeWidget($w)
    {
        return '
            <style type="text/css">
                .success {
                    margin: 20px 0 0;
                    padding: 10px 5px;
                    background: #CCFFCC;
                    border: 2px solid green;
                    font-weight: bold;
                }
                input[type=email]{
                    width: 180px;
                    padding: 1px 2px;
                    border: 1px solid #CDCDCD;
                    color: #005D99;
                    font-size: 1em;
                }
                .mailjet-subscribe {
                    color: #005D99;
                    background: white;
                    font-size: 1em;
                    font-weight: bold;
                    text-transform: uppercase;
                    border: 1px solid white;
                }
            </style> 
            <script type="text/javascript">

                $(document).ready(function(){
                    $(\'.mailjet-subscribe\').click(function(e){
                        e.preventDefault();
                        var params= 
                            {
                                f: \'getPostListSubscribe\', 
                                list: $(\'#list_id\').val(), 
                                email: $(\'#email\').val(),
                            };
                        console.log(params);
                        $.get(
                            \'/admin/services.php\', 
                            params,
                            function(data){
                                
                                $(\'.response\').removeClass(\'error\').removeClass(\'success\').html(($(data).find(\'message\').text()));
                                if($(data).find(\'rsp\').attr(\'status\') == \'failed\'){
                                    $(\'.response\').addClass(\'error\');
                                } else {
                                    $(\'.response\').addClass(\'success\');
                                }
                            },
                            \'xml\'
                        );
                    });
                });
            </script>
            <div id="mailjet-subscription" class="text">
		        <h2>'.$w->title.'</h2>
		        <form id="mailjet-subscription-form">
		            <fieldset>
		                <p>
		                    <input id="email" name="email" value="" type="email" placeholder="your@email.com" />
		                    <input id="list_id" name="list_id" type="hidden" value="'.$w->list.'" />
		                    <input name="submit" type="submit" class="mailjet-subscribe" value="'.$w->button_text.'">
		                </p>
		            </fieldset>
		        </form>
		        <div class="response"></div>
	        </div>';
    }
}
