document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const searchResults = document.getElementById("searchResults");
    const searchForm = document.getElementById("searchForm");

    // Function to show dropdown with animation
    const showResults = () => {
        searchResults.style.display = "block";
        requestAnimationFrame(() => {
            searchResults.classList.add("show");
        });
    };

    // Function to hide dropdown with animation
    const hideResults = () => {
        searchResults.classList.remove("show");
        setTimeout(() => {
            searchResults.style.display = "none";
        }, 200); // matches CSS transition duration
    };

    searchInput.addEventListener("input", () => {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            hideResults();
            searchResults.innerHTML = "";
            return;
        }

        fetch('/softeng/handlers/search_products.php?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    searchResults.innerHTML = data.map(product => `
                        <a href="product.php?id=${product.id}" class="search-item d-block">
                            <div class="d-flex align-items-center">
                                <img src="${product.image}" class="search-img me-2">
                                <div>
                                    <div>${product.name}</div>
                                    <small>₱${parseFloat(product.price).toFixed(2)}</small>
                                </div>
                            </div>
                        </a>
                    `).join("");
                    showResults();
                } else {
                    searchResults.innerHTML = "<div class='p-2 text-muted'>No results found</div>";
                    showResults();
                }
            })
            .catch(err => {
                console.error(err);
                hideResults();
            });
    });

    // Close results if clicking outside
    document.addEventListener("click", (e) => {
        if (!searchForm.contains(e.target)) hideResults();
    });

    searchForm.addEventListener("submit", e => e.preventDefault());
});