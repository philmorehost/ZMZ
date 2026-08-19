package com.philmoresms.app.ui.components

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.philmoresms.app.ui.theme.TextPrimary
import com.philmoresms.app.ui.theme.TextSecondary

@Composable
fun LabeledDropdown(
    label: String,
    selected: String,
    options: List<String>,
    placeholder: String,
    onSelect: (String) -> Unit
) {
    Column(modifier = Modifier.fillMaxWidth().padding(bottom = 20.dp)) {
        Text(
            text = label.uppercase(),
            fontSize = 12.sp,
            color = TextSecondary,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(bottom = 8.dp)
        )
        var expanded by remember { mutableStateOf(false) }
        Box(modifier = Modifier.fillMaxWidth()) {
            OutlinedButton(onClick = { expanded = true }, modifier = Modifier.fillMaxWidth()) {
                Text(
                    text = if (selected.isNotEmpty()) selected else placeholder,
                    color = TextPrimary,
                    modifier = Modifier.weight(1f)
                )
                Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = TextSecondary)
            }
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                options.forEach { option ->
                    DropdownMenuItem(
                        text = { Text(option) },
                        onClick = {
                            onSelect(option)
                            expanded = false
                        }
                    )
                }
            }
        }
    }
}
