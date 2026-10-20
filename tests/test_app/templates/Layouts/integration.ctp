<html>
<body>
<?php echo $this->Flash->render(); ?>
<?php echo $this->Flash->render('auth'); ?>
<?php echo $this->fetch('content'); ?>
</body>
</html>
