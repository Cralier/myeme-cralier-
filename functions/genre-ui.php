<?php
require_once get_template_directory() . '/functions/genre-data.php';

function render_handmade_genre_selector($selected_genres = []) {
  $all_genres = get_handmade_genres();

  echo '<section class="genre-section">';
  echo '<div class="genre-selector">';

  // ▼ 上段（選択済み＋トグルボタン）
  echo '<div class="genre-selected-wrap">';
  echo '  <div class="genre-selected">';
  foreach ($selected_genres as $genre) {
    echo '<span class="genre-tag selected" data-genre="' . esc_attr($genre) . '">' . esc_html($genre) . '</span>';
  }
  echo '  </div>';

  echo '<span class="genre-toggle" id="toggle-button">… 全て表示</span>';

  echo '</div>';

  // ▼ 下段（全ジャンル）
  echo '<div class="genre-dropdown">';
  foreach ($all_genres as $genre) {
    $is_selected = in_array($genre, $selected_genres);
    $class = $is_selected ? 'genre-tag selected' : 'genre-tag';
    echo '<span class="' . esc_attr($class) . '" data-genre="' . esc_attr($genre) . '">' . esc_html($genre) . '</span>';
  }
  echo '</div>';

  echo '<input type="hidden" name="handmade_genres" id="handmade_genres_input" value="' . esc_attr(implode(',', $selected_genres)) . '">';

  echo '</div></section>';
}
?>