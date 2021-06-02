<?php
//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright © 2015-2020 Artur Sierzant                            |
//  | http://www.ompd.pl                                                     |
//  |                                                                        |
//  |                                                                        |
//  | This program is free software: you can redistribute it and/or modify   |
//  | it under the terms of the GNU General Public License as published by   |
//  | the Free Software Foundation, either version 3 of the License, or      |
//  | (at your option) any later version.                                    |
//  |                                                                        |
//  | This program is distributed in the hope that it will be useful,        |
//  | but WITHOUT ANY WARRANTY; without even the implied warranty of         |
//  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          |
//  | GNU General Public License for more details.                           |
//  |                                                                        |
//  | You should have received a copy of the GNU General Public License      |
//  | along with this program.  If not, see <http://www.gnu.org/licenses/>.  |
//  +------------------------------------------------------------------------+

global $cfg, $db;
require_once('include/initialize.inc.php');
require_once('include/library.inc.php');

$album_id = $_POST['album_id'];
$action = $_POST['action'];

$data = array();
$data["result"] = "error";
$isInLibrary = false;

$query = mysqli_query($db,"SELECT * FROM album_id WHERE path LIKE '" . mysqli_real_escape_string($db,$album_id) . ";%'");
if (mysqli_num_rows($query) > 0) {
  $isInLibrary = true;
}

if ($action == "add") {
  if (!$isInLibrary) {
    $ompd_album_id = base_convert(uniqid(), 16, 36);
    $album_add_time = time();
    if (isTidal($album_id)) {
      $query = mysqli_query($db,"SELECT * FROM tidal_album WHERE album_id = " . mysqli_real_escape_string($db,getTidalId($album_id)) . " LIMIT 1");
      $album = mysqli_fetch_assoc($query);

      mysqli_query($db,'INSERT INTO album (artist_alphabetic, artist, album, year, month, genre_id, album_add_time, discs, image_id, album_id, updated, album_dr)
            VALUES (
            "' . $album['artist_alphabetic'] . '",
            "' . $album['artist'] . '",
            "' . $album['album'] . '",
            ' . (is_null($album['album_date']) ? 'NULL' : (int) substr($album['album_date'],0,4)) . ',
            ' . 'NULL' . ',
            ' . (int) $album['genre_id'] . ',
            ' . (int) $album_add_time . ',
            ' . (int) $album['discs'] . ',
            "' . '' . '",
            "' . $ompd_album_id . '",
            9,
            ' . 'NULL' . ')'); 

      $query = mysqli_query($db, "
          INSERT INTO album_id
              (album_id, path, album_add_time, updated)
          VALUES
              ('" . $ompd_album_id . "',
              '" . $album_id . ";" . $album['cover'] . ";" . $album['audio_quality'] . "',
              '" . $album_add_time . "','9')"
      );

      $data["ompd_album_id"] = $album["artist"];
      
    }
  }
  $data["result"] = "add_ok";
}
elseif ($action == "remove") {
  $query = mysqli_query($db,"SELECT album_id FROM album_id WHERE path LIKE '" . mysqli_real_escape_string($db,$album_id) . ";%' AND updated = '9'");

  $album = mysqli_fetch_assoc($query);
  $ompd_album_id = $album['album_id'];

  $query1 = mysqli_query($db,"DELETE FROM album_id WHERE album_id = '" . $ompd_album_id . "'");
  $query2 = mysqli_query($db,"DELETE FROM album WHERE album_id = '" . $ompd_album_id . "'");
  if ($query && $query2) {
    $data["result"] = "remove_ok";
  }
}

echo safe_json_encode($data);	

?>
	
