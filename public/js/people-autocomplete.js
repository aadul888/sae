(function () {
    const inputs = document.querySelectorAll('[data-people-search]');
    if (!inputs.length) return;
    const states = new WeakMap();

    function setTargetValue(selector, value) {
        if (!selector) return null;
        const target = document.querySelector(selector);
        if (target) {
            target.value = value || '';
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return target;
    }

    inputs.forEach(function (input, inputIndex) {
        const container = input.parentElement;
        if (!container) return;
        container.classList.add('people-autocomplete');

        const results = document.createElement('div');
        results.id = `people-search-results-${inputIndex}`;
        results.setAttribute('role', 'listbox');
        results.className = 'people-autocomplete-results';
        container.appendChild(results);
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', results.id);

        let timer = null;
        let controller = null;

        function selectedPeople() {
            const target = input.dataset.peopleMultipleTarget ? document.querySelector(input.dataset.peopleMultipleTarget) : null;
            return target ? Array.from(target.querySelectorAll('input[type="checkbox"]')) : [];
        }

        function updateMultipleValidity() {
            if (input.dataset.peopleMultipleRequired !== 'true') return;
            const hasSelection = selectedPeople().some(function (checkbox) { return checkbox.checked; });
            input.setCustomValidity(hasSelection ? '' : 'Pilih minimal satu GTK dari hasil pencarian.');
        }

        function addMultiplePerson(person) {
            const target = document.querySelector(input.dataset.peopleMultipleTarget);
            if (!target) return;
            let checkbox = selectedPeople().find(function (item) { return item.value === String(person.id); });
            if (checkbox) {
                checkbox.checked = true;
            } else {
                const row = document.createElement('label');
                row.className = 'people-autocomplete-selected';
                checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = input.dataset.peopleMultipleName || 'people_ids[]';
                checkbox.value = person.id;
                checkbox.checked = true;
                checkbox.addEventListener('change', updateMultipleValidity);
                const label = document.createElement('span');
                label.textContent = [person.nama, person.identifier].filter(Boolean).join(' | ');
                row.append(checkbox, label);
                target.appendChild(row);
            }
            updateMultipleValidity();
        }

        function hideResults() {
            results.classList.remove('is-open');
            results.replaceChildren();
        }

        function selectPerson(person) {
            if (input.dataset.peopleMultipleTarget) {
                addMultiplePerson(person);
                input.value = '';
                hideResults();
                return;
            }
            input.value = person.nama;
            setTargetValue(input.dataset.peopleTarget, person.id);
            setTargetValue(input.dataset.peopleNameTarget, person.nama);

            let personType = person.type;
            if (personType !== 'siswa' && input.dataset.peopleGtkType) {
                personType = input.dataset.peopleGtkType;
            }
            setTargetValue(input.dataset.peopleTypeTarget, personType);
            setTargetValue(input.dataset.peopleUnitTarget, person.context);
            input.setCustomValidity('');
            hideResults();
            input.dispatchEvent(new CustomEvent('people:selected', { bubbles: true, detail: person }));
        }

        states.set(input, { selectPerson: selectPerson });

        function renderResults(people) {
            results.replaceChildren();
            if (!people.length) {
                const empty = document.createElement('div');
                empty.textContent = 'Tidak ada hasil yang cocok.';
                empty.className = 'people-autocomplete-empty';
                results.appendChild(empty);
                results.classList.add('is-open');
                return;
            }

            people.forEach(function (person) {
                const option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.className = 'people-autocomplete-option';

                const name = document.createElement('strong');
                name.textContent = person.nama;
                name.className = 'people-autocomplete-name';
                const details = document.createElement('small');
                details.textContent = [person.identifier, person.context].filter(Boolean).join(' | ');
                details.className = 'people-autocomplete-meta';
                option.append(name, details);
                option.addEventListener('click', function () { selectPerson(person); });
                results.appendChild(option);
            });
            results.classList.add('is-open');
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            if (controller) controller.abort();
            if (!input.dataset.peopleMultipleTarget) {
                setTargetValue(input.dataset.peopleTarget, '');
                setTargetValue(input.dataset.peopleNameTarget, '');
            }
            if (input.dataset.peopleClearUnitOnInput === 'true') {
                setTargetValue(input.dataset.peopleUnitTarget, '');
            }
            input.setCustomValidity(input.dataset.peopleRequireSelection === 'true' ? 'Pilih orang dari hasil pencarian.' : '');

            const query = input.value.trim();
            if (query.length < 2) {
                hideResults();
                return;
            }

            timer = window.setTimeout(async function () {
                controller = new AbortController();
                const params = new URLSearchParams({ q: query, type: input.dataset.peopleKind || 'siswa' });
                if (input.dataset.peopleFilter) {
                    const filter = document.querySelector(input.dataset.peopleFilter);
                    if (filter) params.set(input.dataset.peopleFilterParam || 'rombel_id', filter.value);
                }

                try {
                    const response = await fetch(`${input.dataset.peopleSearch}?${params}`, {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    });
                    if (!response.ok) throw new Error('Pencarian gagal');
                    renderResults(await response.json());
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        results.replaceChildren();
                        const message = document.createElement('div');
                        message.textContent = 'Pencarian gagal. Silakan coba lagi.';
                        message.className = 'people-autocomplete-empty';
                        results.appendChild(message);
                        results.classList.add('is-open');
                    }
                }
            }, 250);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') hideResults();
            if (event.key === 'ArrowDown' && results.firstElementChild) {
                event.preventDefault();
                results.querySelector('button[role="option"]')?.focus();
            }
        });

        results.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideResults();
                input.focus();
            }
        });

        if (input.dataset.peopleMultipleRequired === 'true' && input.form) {
            input.form.addEventListener('submit', function (event) {
                updateMultipleValidity();
                if (!input.checkValidity()) {
                    event.preventDefault();
                    input.reportValidity();
                }
            });
        }
    });

    document.addEventListener('click', function (event) {
        inputs.forEach(function (input, inputIndex) {
            const results = document.getElementById(`people-search-results-${inputIndex}`);
            if (results && !input.parentElement.contains(event.target)) results.classList.remove('is-open');
        });
    });

    window.SAEPeopleAutocomplete = {
        async setSelection(input, id) {
            const state = states.get(input);
            if (!state || !id) return false;
            const params = new URLSearchParams({ id: id, type: input.dataset.peopleKind || 'siswa' });
            try {
                const response = await fetch(`${input.dataset.peopleSearch}?${params}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) return false;
                const people = await response.json();
                if (!people.length) return false;
                state.selectPerson(people[0]);
                return true;
            } catch (_) {
                return false;
            }
        },
    };
})();
