<table class="table">
    <thead>
    <tr>

            <th>id</th>
            <th>订单号</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $item)
                <td>{!! $item !!}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
