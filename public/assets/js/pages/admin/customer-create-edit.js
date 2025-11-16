'use strict'

const baseUrl = $('#base_url').val()

const getSuperiorsBySelectedPosition = (selectedPosition) => {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  $.ajax({
    type: 'GET',
    url: baseUrl + '/admin/superiors',
    data: { selected_position: selectedPosition },
    dataType: 'json',
    success: (response) => {
      const superiorSelect = $('#superior');
      superiorSelect.append($('<option>').text('Select superior').attr('value', ''));
      $.each(response, (_, { id, name }) => {
        superiorSelect.append($('<option>').text(name).attr('value', id));
      });
    },
    error: (xhr, status, error) => {
      console.log(error)
    }
  });
}

$(document).ready(function () {
  const positionSelect = $('#position')
  const superiorField = $('#superior-field')
  const currentPosition = positionSelect.val()

  if (currentPosition && currentPosition.toLowerCase() !== 'ceo') {
    superiorField.show()
  } else {
    superiorField.hide()
  }

  positionSelect.on('change', function () {
    const selectedPosition = $(this).val()
    const superiorSelect = $('#superior');
    superiorSelect.empty();

    if (selectedPosition.toLowerCase() === 'ceo') {
      superiorField.hide()
    } else {
      superiorField.show()
      if (selectedPosition) {
        getSuperiorsBySelectedPosition(selectedPosition)
      }
    }
  })
})
