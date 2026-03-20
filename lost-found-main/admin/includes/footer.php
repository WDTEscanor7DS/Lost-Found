            <!-- / CONTENT AREA-->
            </div>

            <footer class="content-footer footer bg-footer-theme">
                <div class="container-fluid">
                    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                        <div class="mb-2 mb-md-0">
                            &copy;
                            <script>
                                document.write(new Date().getFullYear());
                            </script>
                            Lost &amp; Found System
                        </div>
                    </div>
                </div>
            </footer>

            <div class="content-backdrop fade"></div>
            </div>
            </div>
            </div>

            <div class="layout-overlay layout-menu-toggle"></div>
            </div>

            <script src="../assets/vendor/libs/jquery/jquery.js"></script>
            <script src="../assets/vendor/libs/popper/popper.js"></script>
            <script src="../assets/vendor/js/bootstrap.js"></script>
            <script src="../assets/vendor/libs/node-waves/node-waves.js"></script>
            <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
            <script src="../assets/vendor/js/menu.js"></script>
            <script src="../assets/js/sweetalert2.all.min.js"></script>
            <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
            <script src="../assets/js/main.js?v=2"></script>
            <script>
                /* Sidebar toggle — inline so it works regardless of JS file caching */
                (function() {
                    var btn = document.getElementById('sidebar-toggle');
                    if (!btn) return;
                    var fresh = btn.cloneNode(true);
                    btn.parentNode.replaceChild(fresh, btn);
                    fresh.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var html = document.documentElement;
                        html.classList.toggle('layout-menu-collapsed');
                        document.body.classList.toggle('sidebar-collapsed');
                        try {
                            localStorage.setItem('sidebarCollapsed', html.classList.contains('layout-menu-collapsed') ? '1' : '0');
                        } catch (ex) {}
                    });
                    try {
                        if (localStorage.getItem('sidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed');
                    } catch (e) {}
                })();
            </script>
            <script src="../assets/js/dashboards-analytics.js"></script>

            <script>
                (function() {
                    var navItems = <?php echo json_encode($navSearchItems ?? []); ?>;
                    var input = document.getElementById('navSearch');
                    var results = document.getElementById('navSearchResults');
                    if (!input || !results) return;

                    input.addEventListener('input', function() {
                        var query = this.value.toLowerCase().trim();
                        if (!query) {
                            results.style.display = 'none';
                            return;
                        }
                        var filtered = navItems.filter(function(item) {
                            return item.label.toLowerCase().indexOf(query) !== -1;
                        });
                        if (filtered.length === 0) {
                            results.innerHTML = '<div class="nav-search-no-result">No results found</div>';
                        } else {
                            results.innerHTML = filtered.map(function(item) {
                                return '<a href="' + item.url + '"><i class="' + item.icon + '"></i>' + item.label + '</a>';
                            }).join('');
                        }
                        results.style.display = 'block';
                    });

                    document.addEventListener('click', function(e) {
                        if (!input.contains(e.target) && !results.contains(e.target)) {
                            results.style.display = 'none';
                        }
                    });

                    input.addEventListener('keydown', function(e) {
                        var items = results.querySelectorAll('a');
                        var current = results.querySelector('a.active');
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            if (!current && items.length) {
                                items[0].classList.add('active');
                            } else if (current && current.nextElementSibling) {
                                current.classList.remove('active');
                                current.nextElementSibling.classList.add('active');
                            }
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            if (current && current.previousElementSibling) {
                                current.classList.remove('active');
                                current.previousElementSibling.classList.add('active');
                            }
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            var active = results.querySelector('a.active');
                            if (active) {
                                window.location.href = active.href;
                            }
                        }
                    });
                })();
            </script>
            </body>

            </html>