<div class="movie-table-card">

    <h2>Movie List</h2>

    <table class="movie-table">

        <thead>
            <tr>
                <th>S.N</th>
                <th>Title</th>
                <th>Genre</th>
                <th>Language</th>
                <th>Duration</th>
                <th>Release Date</th>
                <th>Format</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php
        $i = 1;

        if(mysqli_num_rows($movie_result) > 0):

            while($movie = mysqli_fetch_assoc($movie_result)):
        ?>

            <tr>

                <td><?= $i++ ?></td>

                <td>
                    <?= htmlspecialchars($movie['title']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($movie['genre']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($movie['language']) ?>
                </td>

                <td>
                    <?= $movie['duration_minutes'] ?> min
                </td>

                <td>
                    <?= $movie['release_date'] ?>
                </td>

                <td>
                    <?= $movie['movie_format'] ?>
                </td>

                <td>
                    <span class="<?= strtolower($movie['status']) ?>">
                        <?= ucfirst(strtolower($movie['status'])) ?>
                    </span>
                </td>

                <td>

                    <a
                        href="edit_movie.php?id=<?= $movie['movie_id'] ?>"
                        class="edit-btn"
                    >
                        Edit
                    </a>

                    <a
                        href="toggle_movie.php?id=<?= $movie['movie_id'] ?>"
                        class="cancel-btn"
                        onclick="return confirm('Change movie status?')"
                    >
                        Status
                    </a>

                </td>

            </tr>

        <?php
            endwhile;

        else:
        ?>

            <tr>
                <td colspan="9">
                    No movies found
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>