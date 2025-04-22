<?php if (count($errors) > 0): ?>
  <?php foreach ($errors as $error): ?>
    <div class="error"><?php echo $error ?></div>
  <?php endforeach ?>
<?php else: ?>      
    <div class="buttons">
      <div class="right">
        <a class="button" href="<?php echo $process_order ?>"><?php echo $button_confirm ?></a>
      </div>
    </div>    
<?php endif ?>
