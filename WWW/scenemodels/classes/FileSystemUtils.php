<?php

/*
 * Copyright (C) 2015 FlightGear Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * FileSystemUtils
 *
 * @author Julien Nguyen
 */
class FileSystemUtils {
    // Deletes a directory sent in parameter
    // =====================================

    public static function clearDir($folder) {
        $opened_dir = opendir($folder);
        if (!$opened_dir) {
            return;
        }

        while ($file = readdir($opened_dir)) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (is_dir($folder."/".$file)) {
                $r = $this->clear_dir($folder."/".$file);
            } else {
                $r = @unlink($folder."/".$file);
            }

            if (!$r) {
                return false;
            }
        }

        closedir($opened_dir);
        return rmdir($folder);
    }
}
