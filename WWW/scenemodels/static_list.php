<?php
include 'inc/header.php';

$query = "SELECT mo_id, mo_path, mo_name FROM fgs_models WHERE mo_shared = 0 ORDER BY mo_id LIMIT 99;";

            $result=pg_query($query);
            while ($row = pg_fetch_assoc($result)) {
        ?>
            <a href="/modelview.php?id=<?php echo $row['mo_id'];?>">
            <img title="<?php echo $row['mo_name'].' ['.$row['mo_path'].']';?>"
                src="modelthumb.php?id=<?php echo $row['mo_id'];?>" width="100" height="75"
                onmouseover="showtrail('modelthumb.php?id=<?php echo $row['mo_id'];?>','','','1',5,322);"
                onmouseout="hidetrail();"
                alt="" />
        </a>
        <?php
        }
include 'inc/footer.php';?>
