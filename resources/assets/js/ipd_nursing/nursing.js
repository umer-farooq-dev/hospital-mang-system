document.addEventListener('DOMContentLoaded', loadNursingChart)

// Nursing chart: open the tab named in the URL, vitals line chart, intake/output type list.
function loadNursingChart () {
    if (!$('#nursingTabs').length) {
        return
    }
    if (window.location.hash) {
        let tab = document.querySelector('#nursingTabs a[href="' + window.location.hash + '"]')
        if (tab) {
            bootstrap.Tab.getOrCreateInstance(tab).show()
        }
    }

    let canvas = document.getElementById('vitalsChart')
    if (canvas && typeof Chart !== 'undefined') {
        let series = JSON.parse(canvas.dataset.series)
        let palette = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#14B8A6', '#EC4899', '#6366F1', '#84CC16']
        let labels = [...new Set(series.flatMap(s => s.points.map(p => p.x)))].sort()
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: series.map((s, i) => ({
                    label: s.label,
                    data: labels.map(x => (s.points.find(p => p.x === x) || {}).y ?? null),
                    borderColor: palette[i % palette.length],
                    backgroundColor: palette[i % palette.length],
                    spanGaps: true,
                    tension: 0.2,
                })),
            },
            options: { responsive: true, interaction: { mode: 'index', intersect: false } },
        })
    }

    $('#flowKind').on('change', function () {
        let kind = $(this).val()
        $('#flowCategory option').each(function () {
            let match = $(this).data('kind') === kind
            $(this).prop('hidden', !match).prop('disabled', !match)
        })
        $('#flowCategory').val($('#flowCategory option[data-kind="' + kind + '"]').first().val())
    })
}
