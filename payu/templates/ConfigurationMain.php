<?php

/**
 * @var array $params
 */
?>
<div class="main-settings">
    <form action="options.php" method="POST">
        <?php wp_nonce_field('update-options'); ?>

        <div class="configuration-payu">

            <h1>Настройки плагина PayU для WooCommerce</h1>

            <input type="hidden" name="action" value="update"/>
            <input type="hidden" name="page_options" value="<?= \implode(',', array_keys($params)); ?>"/>

            <table class="form-table">
                <tbody>
                    <?php foreach ($params as $code => $param):?>
                        <tr>
                            <td>
                                <label for="<?=$code;?>"><?=$param['title'];?></label>
                            </td>
                            <td>
                                <?php
                                    switch ($param['type']):

                                        case 'title':?>
                                            <?=$param['description'];?>
                                        <?php break;

                                        case 'text':?>
                                            <input id="<?=$code;?>" type="text" name="<?=$code;?>" value="<?=$param['value'];?>" />
                                        <?php break;

                                        case 'textarea':?>
                                            <textarea id="<?=$code;?>" type="text" name="<?=$code;?>"><?=$param['value'];?></textarea>
                                        <?php break;

                                        case 'select':?>
                                            <select name="<?=$code;?>" id="<?=$code;?>">
                                                <?php foreach($param['options'] as $value => $text):?>
                                                    <option value="<?=$value;?>"<?php if($param['value'] == $value):?> selected="selected"<?php endif;?>><?=$text;?></option>
                                                <?php endforeach;?>
                                            </select>
                                        <?php break;
                                    endswitch;
                                ?>
                            </td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
            </p>
        </form>
    </div>
</div>