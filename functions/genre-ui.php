<?php
require_once get_template_directory() . '/functions/genre-data.php';

function render_handmade_genre_selector($selected_genres = []) {
  $all_genres = get_handmade_genres();

  $selected = array_intersect($all_genres, $selected_genres);
  $remaining = array_diff($all_genres, $selected_genres);

  echo '<section class="genre-section">';
  echo '  <div class="genre-selector">';

  // ▼ 選択済みジャンル（上段）
  echo '    <div class="genre-selected">';
  foreach ($selected as $genre) {
    echo '<span class="genre-tag selected" data-genre="' . esc_attr($genre) . '">' . esc_html($genre) . '</span>';
  }
  echo '      <span class="genre-toggle" id="toggle-button">… 全て表示</span>';
  echo '    </div>';

  // ▼ 全ジャンル（ドロップダウン）
  echo '    <div class="genre-dropdown">';
  foreach ($remaining as $genre) {
    echo '<span class="genre-tag" data-genre="' . esc_attr($genre) . '">' . esc_html($genre) . '</span>';
  }
  echo '    </div>';

  // ▼ hidden input（送信用）
  echo '<input type="hidden" name="handmade_genres" id="handmade_genres_input" value="' . esc_attr(implode(',', $selected_genres)) . '">';

  echo '  </div>';
  echo '</section>';
}
?>