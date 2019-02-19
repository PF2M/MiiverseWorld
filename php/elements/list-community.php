<div class="community-list-body trigger" data-href="/communities/<?=$row['id']?>">
    <?php if(!empty($row['banner'])) { ?><img class="community-list-cover community-list" src="<?php echo htmlspecialchars($row['banner']) . '">'; } ?>
    <span>
        <span class="icon-container">
            <a href="/communities/<?=$row['id']?>">
                <img class="icon" src="<?=htmlspecialchars($row['icon'])?>">
            </a>
        </span>
        <div class="body">
            <a class="title" href="/communities/<?=$row['id'] . '">' . htmlspecialchars($row['name'])?></a>
        </div>
    </span>
</div>