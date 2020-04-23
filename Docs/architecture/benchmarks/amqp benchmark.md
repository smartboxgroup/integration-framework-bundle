# Benchmark results

Median time to insert 1000 messages, via web, with STOMP and AMQP.

## AMQP

| Type            | 1st run | 2nd run | 3rd run | Median |
|-----------------|---------|---------|---------|--------|
| Empty payload   | 96      | 96      | 95      | **96** |
| Complex payload | 106     | 107     | 105     | **106**|


## STOMP

| Type            | 1st run | 2nd run | 3rd run | Median |
|-----------------|---------|---------|---------|--------|
| Empty payload   | 109     | 108     | 108     | **108**|
| Complex payload | 117     | 117     | 119     | **117**|
